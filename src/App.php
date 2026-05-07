<?php

declare(strict_types=1);

namespace DeviceStore;

use DeviceStore\Core\Auth;
use DeviceStore\Core\Csrf;
use DeviceStore\Core\FileDatabase;
use DeviceStore\Core\Validator;
use DeviceStore\Repositories\CategoryRepository;
use DeviceStore\Repositories\OrderRepository;
use DeviceStore\Repositories\ProductRepository;
use DeviceStore\Repositories\UserRepository;

/**
 * Основной класс приложения: связывает маршруты с обработчиками страниц.
 */
final class App
{
    /** @var array<string, mixed> */
    private array $config;

    private Auth $auth;

    private UserRepository $users;

    private CategoryRepository $categories;

    private ProductRepository $products;

    private OrderRepository $orders;

    /**
     * Создает репозитории, авторизацию и сервис базы данных.
     *
     * @param array<string, mixed> $config Конфигурация приложения.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $db = new FileDatabase((string) $config['database_path']);
        $this->users = new UserRepository($db);
        $this->categories = new CategoryRepository($db);
        $this->products = new ProductRepository($db);
        $this->orders = new OrderRepository($db);
        $this->auth = new Auth($this->users);
    }

    /**
     * Обрабатывает текущий HTTP-запрос.
     *
     * @return void
     */
    public function handle(): void
    {
        $page = (string) ($_GET['page'] ?? 'home');

        match ($page) {
            'home' => $this->home(),
            'catalog' => $this->catalog(),
            'product' => $this->product(),
            'register' => $this->register(),
            'login' => $this->login(),
            'logout' => $this->logout(),
            'forgot' => $this->forgot(),
            'reset' => $this->reset(),
            'orders' => $this->userOrders(),
            'admin' => $this->adminDashboard(),
            'admin-products' => $this->adminProducts(),
            'admin-product-create' => $this->adminProductCreate(),
            'admin-product-edit' => $this->adminProductEdit(),
            'admin-product-delete' => $this->adminProductDelete(),
            'admin-categories' => $this->adminCategories(),
            'admin-orders' => $this->adminOrders(),
            'admin-users' => $this->adminUsers(),
            default => $this->notFound(),
        };
    }

    /**
     * Отображает публичную главную страницу.
     *
     * @return void
     */
    private function home(): void
    {
        $this->render('home', [
            'featuredProducts' => $this->products->featured(3),
            'latestProducts' => array_slice($this->products->all(), 0, 3),
            'categories' => $this->categories->all(),
            'stats' => [
                'products' => count($this->products->all()),
                'categories' => count($this->categories->all()),
                'orders' => $this->orders->count(),
            ],
        ], 'Главная');
    }

    /**
     * Отображает публичный каталог товаров с поиском.
     *
     * @return void
     */
    private function catalog(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'product_type' => (string) ($_GET['product_type'] ?? ''),
            'max_price' => (float) ($_GET['max_price'] ?? 0),
            'available' => (string) ($_GET['available'] ?? ''),
        ];

        $this->render('catalog', [
            'products' => $this->products->search($filters),
            'categories' => $this->categories->all(),
            'categoryMap' => $this->categoryMap(),
            'filters' => $filters,
        ], 'Каталог');
    }

    /**
     * Отображает страницу товара и обрабатывает создание заказа.
     *
     * @return void
     */
    private function product(): void
    {
        $product = $this->products->find((int) ($_GET['id'] ?? 0));

        if ($product === null) {
            $this->notFound();

            return;
        }

        $errors = [];
        $old = [];

        if ($this->isPost()) {
            $this->requireLogin();
            $this->requireValidCsrf('product', ['id' => (int) $product['id']]);
            $result = Validator::order($_POST, $product);
            $errors = $result['errors'];
            $old = $result['data'];

            if ($errors === []) {
                $user = $this->auth->user();
                $quantity = (int) $old['quantity'];
                $this->orders->create([
                    'user_id' => (int) $user['id'],
                    'product_id' => (int) $product['id'],
                    'quantity' => $quantity,
                    'unit_price' => (float) $product['price'],
                    'total_price' => (float) $product['price'] * $quantity,
                    'customer_telegram' => $old['customer_telegram'],
                    'customer_phone' => $old['customer_phone'],
                    'delivery_address' => $old['delivery_address'],
                    'note' => $old['note'],
                ]);
                $this->products->decreaseStock((int) $product['id'], $quantity);
                flash('success', 'Заказ создан. Администратор увидит его в панели управления.');
                redirect('orders');
            }
        }

        $this->render('product', [
            'product' => $product,
            'category' => $this->categories->find((int) $product['category_id']),
            'errors' => $errors,
            'old' => $old,
        ], (string) $product['name']);
    }

    /**
     * Отображает и обрабатывает регистрацию пользователя.
     *
     * @return void
     */
    private function register(): void
    {
        if ($this->auth->check()) {
            redirect('home');
        }

        $errors = [];
        $old = [];

        if ($this->isPost()) {
            $this->requireValidCsrf('register');
            $result = Validator::register($_POST);
            $errors = $result['errors'];
            $old = $result['data'];

            if ($errors === [] && $this->users->findByEmail((string) $old['email']) !== null) {
                $errors['email'] = 'Пользователь с таким email уже существует.';
            }

            if ($errors === []) {
                $user = $this->users->create(
                    (string) $old['name'],
                    (string) $old['email'],
                    (string) $old['password']
                );
                $this->auth->login($user);
                flash('success', 'Регистрация завершена. Теперь можно оформлять заказы.');
                redirect('home');
            }
        }

        $this->render('auth/register', [
            'errors' => $errors,
            'old' => $old,
        ], 'Регистрация');
    }

    /**
     * Отображает и обрабатывает вход пользователя.
     *
     * @return void
     */
    private function login(): void
    {
        if ($this->auth->check()) {
            redirect('home');
        }

        $errors = [];
        $old = [];

        if ($this->isPost()) {
            $this->requireValidCsrf('login');
            $result = Validator::login($_POST);
            $errors = $result['errors'];
            $old = $result['data'];

            if ($errors === [] && !$this->auth->attempt((string) $old['email'], (string) $old['password'])) {
                $errors['password'] = 'Неверный email или пароль.';
            }

            if ($errors === []) {
                flash('success', 'Вы вошли в систему.');
                redirect($this->auth->isAdmin() ? 'admin' : 'home');
            }
        }

        $this->render('auth/login', [
            'errors' => $errors,
            'old' => $old,
        ], 'Вход');
    }

    /**
     * Завершает сессию текущего пользователя.
     *
     * @return void
     */
    private function logout(): void
    {
        if (!$this->isPost()) {
            redirect('home');
        }

        $this->requireValidCsrf('home');
        $this->auth->logout();
        flash('success', 'Вы вышли из аккаунта.');
        redirect('home');
    }

    /**
     * Отображает и обрабатывает запрос восстановления пароля.
     *
     * @return void
     */
    private function forgot(): void
    {
        $errors = [];
        $old = [];
        $resetUrl = null;

        if ($this->isPost()) {
            $this->requireValidCsrf('forgot');
            $result = Validator::forgot($_POST);
            $errors = $result['errors'];
            $old = $result['data'];

            if ($errors === []) {
                $token = $this->users->createResetToken((string) $old['email']);

                if ($token !== null) {
                    $resetUrl = url('reset', ['token' => $token]);
                }

                flash('success', 'Если email есть в системе, ссылка восстановления создана.');
            }
        }

        $this->render('auth/forgot', [
            'errors' => $errors,
            'old' => $old,
            'resetUrl' => $resetUrl,
        ], 'Восстановление пароля');
    }

    /**
     * Отображает и обрабатывает установку нового пароля.
     *
     * @return void
     */
    private function reset(): void
    {
        $token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
        $user = $this->users->findByResetToken($token);

        if ($token === '' || $user === null) {
            $this->render('auth/reset_invalid', [], 'Ссылка недействительна');

            return;
        }

        $errors = [];

        if ($this->isPost()) {
            $this->requireValidCsrf('reset', ['token' => $token]);
            $result = Validator::resetPassword($_POST);
            $errors = $result['errors'];

            if ($errors === []) {
                $this->users->updatePassword((int) $user['id'], (string) $result['data']['password']);
                flash('success', 'Пароль обновлен. Войдите с новым паролем.');
                redirect('login');
            }
        }

        $this->render('auth/reset', [
            'token' => $token,
            'errors' => $errors,
        ], 'Новый пароль');
    }

    /**
     * Отображает заказы текущего пользователя.
     *
     * @return void
     */
    private function userOrders(): void
    {
        $this->requireLogin();
        $user = $this->auth->user();

        $this->render('orders', [
            'orders' => $this->orders->forUser((int) $user['id']),
            'productMap' => $this->productMap(),
        ], 'Мои заказы');
    }

    /**
     * Отображает панель администратора.
     *
     * @return void
     */
    private function adminDashboard(): void
    {
        $this->requireAdmin();

        $this->render('admin/dashboard', [
            'stats' => [
                'products' => count($this->products->all()),
                'categories' => count($this->categories->all()),
                'orders' => $this->orders->count(),
                'newOrders' => $this->orders->count('new'),
                'lowStock' => $this->products->countLowStock(),
                'users' => count($this->users->all()),
            ],
        ], 'Админ-панель');
    }

    /**
     * Отображает список товаров для администратора.
     *
     * @return void
     */
    private function adminProducts(): void
    {
        $this->requireAdmin();

        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'product_type' => (string) ($_GET['product_type'] ?? ''),
            'max_price' => (float) ($_GET['max_price'] ?? 0),
            'available' => (string) ($_GET['available'] ?? ''),
        ];

        $this->render('admin/products', [
            'products' => $this->products->search($filters),
            'categories' => $this->categories->all(),
            'categoryMap' => $this->categoryMap(),
            'filters' => $filters,
        ], 'Управление товарами');
    }

    /**
     * Отображает и обрабатывает создание товара.
     *
     * @return void
     */
    private function adminProductCreate(): void
    {
        $this->requireAdmin();
        $errors = [];
        $old = [];

        if ($this->isPost()) {
            $this->requireValidCsrf('admin-product-create');
            $result = Validator::product($_POST);
            $errors = $result['errors'];
            $old = $result['data'];

            if ($errors === [] && $this->categories->find((int) $old['category_id']) === null) {
                $errors['category_id'] = 'Выбранная категория не найдена.';
            }

            if ($errors === []) {
                $this->products->create($old);
                flash('success', 'Товар добавлен.');
                redirect('admin-products');
            }
        }

        $this->render('admin/product_form', [
            'mode' => 'create',
            'product' => null,
            'categories' => $this->categories->all(),
            'errors' => $errors,
            'old' => $old,
        ], 'Добавление товара');
    }

    /**
     * Отображает и обрабатывает редактирование товара.
     *
     * @return void
     */
    private function adminProductEdit(): void
    {
        $this->requireAdmin();
        $product = $this->products->find((int) ($_GET['id'] ?? 0));

        if ($product === null) {
            $this->notFound();

            return;
        }

        $errors = [];
        $old = $product;

        if ($this->isPost()) {
            $this->requireValidCsrf('admin-product-edit', ['id' => (int) $product['id']]);
            $result = Validator::product($_POST);
            $errors = $result['errors'];
            $old = $result['data'];

            if ($errors === [] && $this->categories->find((int) $old['category_id']) === null) {
                $errors['category_id'] = 'Выбранная категория не найдена.';
            }

            if ($errors === []) {
                $this->products->update((int) $product['id'], $old);
                flash('success', 'Товар обновлен.');
                redirect('admin-products');
            }
        }

        $this->render('admin/product_form', [
            'mode' => 'edit',
            'product' => $product,
            'categories' => $this->categories->all(),
            'errors' => $errors,
            'old' => $old,
        ], 'Редактирование товара');
    }

    /**
     * Обрабатывает удаление товара.
     *
     * @return void
     */
    private function adminProductDelete(): void
    {
        $this->requireAdmin();

        if ($this->isPost()) {
            $this->requireValidCsrf('admin-products');
            $this->products->delete((int) ($_POST['id'] ?? 0));
            flash('success', 'Товар удален.');
        }

        redirect('admin-products');
    }

    /**
     * Отображает и обрабатывает управление категориями.
     *
     * @return void
     */
    private function adminCategories(): void
    {
        $this->requireAdmin();
        $errors = [];
        $old = [];

        if ($this->isPost()) {
            $this->requireValidCsrf('admin-categories');
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'create') {
                $result = Validator::category($_POST);
                $errors = $result['errors'];
                $old = $result['data'];

                if ($errors === []) {
                    $this->categories->create((string) $old['name']);
                    flash('success', 'Категория добавлена.');
                    redirect('admin-categories');
                }
            }

            if ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);

                if ($this->products->countByCategory($id) > 0) {
                    flash('error', 'Нельзя удалить категорию, пока в ней есть товары.');
                } else {
                    $this->categories->delete($id);
                    flash('success', 'Категория удалена.');
                }

                redirect('admin-categories');
            }
        }

        $this->render('admin/categories', [
            'categories' => $this->categories->all(),
            'productCounts' => $this->categoryProductCounts(),
            'errors' => $errors,
            'old' => $old,
        ], 'Категории');
    }

    /**
     * Отображает и обрабатывает управление заказами.
     *
     * @return void
     */
    private function adminOrders(): void
    {
        $this->requireAdmin();

        if ($this->isPost()) {
            $this->requireValidCsrf('admin-orders');
            $this->orders->updateStatus((int) ($_POST['id'] ?? 0), (string) ($_POST['status'] ?? 'new'));
            flash('success', 'Статус заказа обновлен.');
            redirect('admin-orders');
        }

        $this->render('admin/orders', [
            'orders' => $this->orders->all(),
            'productMap' => $this->productMap(),
            'userMap' => $this->userMap(),
        ], 'Заказы');
    }

    /**
     * Отображает и обрабатывает создание учетной записи администратора.
     *
     * @return void
     */
    private function adminUsers(): void
    {
        $this->requireAdmin();
        $errors = [];
        $old = [];

        if ($this->isPost()) {
            $this->requireValidCsrf('admin-users');
            $result = Validator::adminUser($_POST);
            $errors = $result['errors'];
            $old = $result['data'];

            if ($errors === [] && $this->users->findByEmail((string) $old['email']) !== null) {
                $errors['email'] = 'Пользователь с таким email уже существует.';
            }

            if ($errors === []) {
                $this->users->create(
                    (string) $old['name'],
                    (string) $old['email'],
                    (string) $old['password'],
                    'admin'
                );
                flash('success', 'Новая учетная запись администратора создана.');
                redirect('admin-users');
            }
        }

        $this->render('admin/users', [
            'users' => $this->users->all(),
            'errors' => $errors,
            'old' => $old,
        ], 'Администраторы');
    }

    /**
     * Отображает страницу 404.
     *
     * @return void
     */
    private function notFound(): void
    {
        http_response_code(404);
        $this->render('errors/404', [], 'Страница не найдена');
    }

    /**
     * Проверяет, что текущий посетитель вошел в систему.
     *
     * @return void
     */
    private function requireLogin(): void
    {
        if (!$this->auth->check()) {
            flash('error', 'Для доступа к этому разделу войдите в систему.');
            redirect('login');
        }
    }

    /**
     * Проверяет, что текущий посетитель является администратором.
     *
     * @return void
     */
    private function requireAdmin(): void
    {
        if (!$this->auth->isAdmin()) {
            flash('error', 'Этот раздел доступен только администратору.');
            redirect('login');
        }
    }

    /**
     * Проверяет, что POST-запрос содержит корректный CSRF-токен.
     *
     * @param string $fallbackPage Страница для перенаправления при ошибке проверки.
     * @param array<string, mixed> $params Параметры ссылки для перенаправления.
     * @return void
     */
    private function requireValidCsrf(string $fallbackPage, array $params = []): void
    {
        if (!Csrf::validate((string) ($_POST['csrf_token'] ?? ''))) {
            flash('error', 'Сессия формы истекла. Повторите действие.');
            redirect($fallbackPage, $params);
        }
    }

    /**
     * Проверяет, является ли текущий запрос POST-запросом.
     *
     * @return bool True для POST-запросов.
     */
    private function isPost(): bool
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
    }

    /**
     * Отображает шаблон внутри основного макета.
     *
     * @param string $view Путь к шаблону внутри src/Views без расширения.
     * @param array<string, mixed> $params Переменные, доступные в шаблоне.
     * @param string $title Заголовок страницы.
     * @return void
     */
    private function render(string $view, array $params, string $title): void
    {
        $viewPath = BASE_PATH . '/src/Views/' . $view . '.php';
        $auth = $this->auth;
        $appName = (string) $this->config['app_name'];
        $currentPage = (string) ($_GET['page'] ?? 'home');
        $flashMessages = consume_flash();

        extract($params, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require BASE_PATH . '/src/Views/layout.php';
    }

    /**
     * Формирует карту названий категорий по их идентификаторам.
     *
     * @return array<int, string> Карта категорий.
     */
    private function categoryMap(): array
    {
        $map = [];

        foreach ($this->categories->all() as $category) {
            $map[(int) $category['id']] = (string) $category['name'];
        }

        return $map;
    }

    /**
     * Формирует количество товаров по категориям.
     *
     * @return array<int, int> Карта количества товаров по категориям.
     */
    private function categoryProductCounts(): array
    {
        $counts = [];

        foreach ($this->categories->all() as $category) {
            $counts[(int) $category['id']] = $this->products->countByCategory((int) $category['id']);
        }

        return $counts;
    }

    /**
     * Формирует карту товаров по их идентификаторам.
     *
     * @return array<int, array<string, mixed>> Карта товаров.
     */
    private function productMap(): array
    {
        $map = [];

        foreach ($this->products->all() as $product) {
            $map[(int) $product['id']] = $product;
        }

        return $map;
    }

    /**
     * Формирует карту пользователей по их идентификаторам.
     *
     * @return array<int, array<string, mixed>> Карта пользователей.
     */
    private function userMap(): array
    {
        $map = [];

        foreach ($this->users->all() as $user) {
            $map[(int) $user['id']] = $user;
        }

        return $map;
    }
}
