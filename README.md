# Covoiturage

Mini application de covoiturage réalisée lors d'un test technique chez Zenithis SARL

## Installation

```bash
git clone https://github.com/waufo/covoiturage.git
cd project
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run dev ou php artisan serve
```

📚 Fonctionnalités

 Authentification
 CRUD utilisateurs
 CRUD trajets
 API REST
