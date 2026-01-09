# Best Practices 2025 - Laravel 10 / PHP 8.x / MySQL 8.x

**Date de recherche** : 17 décembre 2024
**Sources** : Web search multi-sources
**Objectif** : Compiler best practices actuelles pour alimenter BEST_PRACTICES.md KLASSCI

---

## 📚 Table des Matières

1. [Laravel 10 Best Practices](#1-laravel-10-best-practices)
2. [Laravel Eloquent Optimization](#2-laravel-eloquent-optimization)
3. [Laravel Service Layer Architecture](#3-laravel-service-layer-architecture)
4. [Laravel Multi-Tenant SaaS](#4-laravel-multi-tenant-saas)
5. [PHP 8.2 Features & Best Practices](#5-php-82-features--best-practices)
6. [PHP 8.2 Performance Optimization](#6-php-82-performance-optimization)
7. [MySQL 8 Indexing Strategies](#7-mysql-8-indexing-strategies)
8. [MySQL 8 JSON Optimization](#8-mysql-8-json-optimization)
9. [Spatie Laravel Permission](#9-spatie-laravel-permission)
10. [Blade Templates Optimization](#10-blade-templates-optimization)
11. [Alpine.js Laravel Integration](#11-alpinejs-laravel-integration)

---

## 1. Laravel 10 Best Practices

### Core Development Practices

**Keep Laravel Updated**
Keeping Laravel up to date provides improved security through regular security fixes, better performance with faster load times and more efficient code, new features and functionality, and compatibility with the latest official and community packages.

**Follow Naming Conventions and PSR Standards**
Follow PSR standards and naming conventions accepted by Laravel community, which eliminates the need for additional configuration when following certain conventions.

**Fat Models, Skinny Controllers**
One of the key Laravel best practices is the "Fat Models, Skinny Controllers" approach, which keeps each part of the MVC architecture focused on its core responsibility, where business logic and database queries live in models, while controllers handle HTTP requests and call model methods.

### Testing & Quality Assurance

**Implement Automated Testing**
Automated testing is a broad yet often underestimated area, however, it is one of the most important best practices for ensuring project reliability. Implement integration tests to verify interactions between different parts of your application, structure your tests using the Arrange-Act-Assert (AAA) pattern to promote readability and maintainability, and use the RefreshDatabase trait to start each test with a clean slate.

### Architecture Best Practices

**Use Built-in Laravel Features**
Prefer to use built-in Laravel functionality and community packages instead of using 3rd party packages and tools.

**Follow Laravel's Directory Structure**
Adhere to the Laravel Directory Structure, which allows you to find and debug your application easily.

**Separate Business Logic**
Never put any logic in routes files. Avoid placing business logic in controllers or routes.

### Database & Performance

**Use Eloquent Effectively**
Prefer to use Eloquent over using Query Builder and raw SQL queries, and prefer collections over arrays.

**Implement Caching**
One of Laravel's REST API best practices involves caching regularly accessed data which doesn't change often, reducing the database data retrieval overhead.

### API Development

**API Best Practices**
Use middleware to implement authorization and permission for API endpoints, which gives your codebase centralized control and improves scalability, and adopt API versioning so that you can maintain various versions of your endpoints and introduce updates without interfering with existing integrations.

### Security

**Security Measures**
Laravel's validation system defends against SQL injection, XSS, and CSRF attacks, Form Request classes let you accept only the data you intend to, and Laravel also generates tokens for CSRF protection automatically.

### Sources

- [GitHub - alexeymezenin/laravel-best-practices](https://github.com/alexeymezenin/laravel-best-practices)
- [19+ Laravel Best Practices for Developers in 2025 | ButterCMS](https://buttercms.com/blog/laravel-best-practices/)
- [7 Laravel Best Practices for Developers in 2025 | Strapi](https://strapi.io/blog/laravel-best-practices)
- [Laravel in 2025: 8 Modern Practices You Should Be Using Now | Medium](https://medium.com/@kamrankhalid06/laravel-in-2025-8-modern-practices-you-should-be-using-now-81584de557da)
- [Laravel Best Practices, Tips, and Tricks for 2025 | DEV Community](https://dev.to/westtan/laravel-best-practices-tips-and-tricks-for-2025-5542)
- [25 Laravel best practices, tips, and tricks (2025) | Benjamin Crozat](https://benjamincrozat.com/laravel-best-practices)
- [Laravel Best Practices 2025 | Aglowiditsolutions](https://aglowiditsolutions.com/blog/laravel-best-practices/)
- [Laravel Best Practices | SaaSykit](https://saasykit.com/blog/laravel-best-practices)
- [19 Laravel security best practices for 2025 | Benjamin Crozat](https://benjamincrozat.com/laravel-security-best-practices)
- [Laravel Best Practices | TatvaSoft](https://www.tatvasoft.com/outsourcing/2025/09/laravel-best-practices.html)

---

## 2. Laravel Eloquent Optimization

### N+1 Query Problem

**Eager Loading**
Use eager loading (`with()` or `load()`) to reduce the number of database queries. This approach fetches all posts and their associated comments in just two queries, regardless of the number of posts.

**Preventing N+1 Issues in Development**
Enable `Model::preventLazyLoading()` during development to catch unintentional lazy loads. From Laravel 8.43 onwards, there's a feature to auto-prevent N+1 issues that's worth activating in local environments.

**Default Eager Loading**
Use the `$with` property in your models to define default relationships that should always be eagerly loaded.

**Exemple** :
```php
class User extends Model
{
    protected $with = ['profile', 'roles'];
}
```

### Advanced Optimization Techniques

**JSON Aggregation (2025)**
A new Laravel query optimizer package (updated December 16, 2025) can reduce multiple Eloquent queries to a single optimized SQL statement using JSON aggregation. This approach transforms 5-15 queries into 1 optimized query, reducing execution time by 80-90%.

### Detection Tools

**Laravel Debugbar & Query Detector**
Leverage Laravel Debugbar and Laravel Query Detector to spot N+1 problems visually and instantly. Tools like Laravel Debugbar, Telescope, and Clockwork can simplify this process.

### Real-World Performance Impact

Even with Laravel's eager loading, developers sometimes still hit the database multiple times per request, resulting in separate queries for partners, profiles, countries, and promocodes. Proper eager loading reduces this significantly.

### Sources

- [shammaa/laravel-optimized-queries | Packagist](https://packagist.org/packages/shammaa/laravel-optimized-queries)
- [How to Fix the N+1 Query Problem in Laravel Eloquent | Medium](https://medium.com/@ashot.bes/understanding-and-resolving-the-n-1-query-problem-in-laravel-eloquent-032a5114ad0a)
- [I Made My Laravel API 83% Faster by Rethinking Database Queries | DEV Community](https://dev.to/raz_galstyan_0f42214efdb8/i-made-my-laravel-api-83-faster-by-rethinking-database-queries-23jh)
- [Laravel Eloquent: Advanced Query Optimization and Profiling Techniques | DEV Community](https://dev.to/arabosman/laravel-eloquent-advanced-query-optimization-and-profiling-techniques-25nn)
- [How To Optimize Laravel Database Queries | WP Web Infotech](https://wpwebinfotech.com/blog/optimize-laravel-database-queries/)
- [Optimizing Laravel Applications: Detecting and Fixing N+1 Query Issues | LoadForge](https://loadforge.com/guides/optimizing-laravel-applications-by-detecting-n1-queries)
- [Mastering Eloquent in Laravel: Relationships, Lazy Loading, and Query Optimization | Medium](https://medium.com/@hasanhawary1/mastering-eloquent-in-laravel-relationships-lazy-loading-and-query-optimization-dde85b92d29a)
- [Guide to Optimizing Laravel Eloquent & Database Speed | Medium](https://medium.com/@laravelprotips/guide-to-optimizing-laravel-eloquent-database-speed-77c3479aec3e)
- [18 Tips to optimize laravel database queries | dudi.dev](https://dudi.dev/optimize-laravel-database-queries/)
- [Laravel Query Profiling: Tools and Techniques | Inspector](https://inspector.dev/laravel-query-profiling-tools-and-techniques/)

---

## 3. Laravel Service Layer Architecture

### Core Concept

The Service Layer is an architectural pattern that promotes separation of concerns and keeps the business logic separate from the presentation and data access layers, acting as an intermediary between controllers and data models while encapsulating complex business rules.

### Key Architecture Patterns in 2025

#### 1. MVCS Architecture (Model-View-Controller-Service)

MVCS adds a Service layer between Controllers and Models, where services handle business logic, keeping controllers thin and models focused on data representation.

**Structure** :
```
Controller → Service → Repository/Model → Database
```

#### 2. Clean Service-Action Architecture

This Clean Service-Action architecture pattern has been implemented across multiple Laravel applications from startups handling thousands of requests to enterprise systems processing millions. The pattern uses repositories for data access and services for business logic.

**Exemple** :
```php
// app/Services/InscriptionService.php
class InscriptionService
{
    public function __construct(
        private InscriptionRepository $repo,
        private NotificationService $notifier
    ) {}

    public function createInscription(array $data): Inscription
    {
        // Business logic here
        $inscription = $this->repo->create($data);
        $this->notifier->sendWelcomeEmail($inscription);
        return $inscription;
    }
}
```

#### 3. Domain-Driven Design (DDD)

Domain-Driven Design organizes code around business logic rather than technical layers, making it easier to maintain and scale.

### Best Practices for 2025

Separating business logic (service layer), data access (models/repositories), and request handling (controllers) makes your app architecture clean and professional, following best programming practices.

**Key benefits** :
- **Improved testability** : Services can be unit tested independently
- **Reusability** : Same service used across multiple controllers
- **Maintainability** : Business logic changes isolated to service layer
- **Better code organization** : Clear separation of concerns

### Sources

- [Mastering Repository Pattern and Service Layer in Laravel | Medium](https://medium.com/@rejwancse10/mastering-repository-pattern-and-service-layer-in-laravel-a-complete-guide-b755354cc231)
- [Service Layer in Laravel — use it! | Medium](https://medium.com/@sliusarchyn/service-layer-in-laravel-use-it-ae861fb0f124)
- [3 Essential Laravel Architecture Best Practices for 2025 | Medium](https://medium.com/@s.h.siddiqui5830/3-essential-laravel-architecture-best-practices-for-2025-0fc12335590a)
- [Clean Service-Action Architecture | Medium](https://ratheepan.medium.com/clean-service-action-architecture-a-battle-tested-pattern-for-laravel-applications-dc311ecc5c29)
- [Service Layer Design Pattern in Laravel (PHP) | Medium](https://medium.com/@mohammad.roshandelpoor/service-layer-design-pattern-in-laravel-php-e132dcb4c2ab)
- [Laravel MVCS Architecture: Complete Development Guide 2025](https://pola5h.github.io/blog/laravel-mvcs-architecture-guide/)
- [Laravel Microservices in 2025 | Abbacus Technologies](https://www.abbacustechnologies.com/laravel-microservices-in-2025-architecture-and-cost-guide/)
- [Layered Architectures with Laravel | Martin Joo](https://martinjoo.dev/layered-architectures-with-laravel)
- [The Hidden Laravel Service Layer | Medium](https://medium.com/@mehdibafdil/the-hidden-laravel-service-layer-masterclass-for-clean-architecture-7c82a0310f5d)
- [Service Layer Laravel Tutorial | Muneeb Dev](https://muneebdev.com/service-layer-laravel-tutorial/)

---

## 4. Laravel Multi-Tenant SaaS

### Multi-Tenancy Architecture Approaches

The three primary architectures include:

#### 1. Single Database with Shared Schema
This is the simplest form of multi-tenancy where all tenants share the same database and same tables, separated by a `tenant_id` column.

**Avantages** : Simple à implémenter
**Inconvénients** : Risques de fuite de données, performances limitées à grande échelle

#### 2. Schema-per-Tenant
A single database holds multiple schemas (one per tenant), offering balance between isolation and resource optimization.

**Avantages** : Meilleure isolation, optimisation ressources
**Inconvénients** : Complexité moyenne, migrations par schéma

#### 3. Database-per-Tenant ⭐ **Recommandé pour KLASSCI**
This is the most secure and scalable option where each tenant has their own dedicated database, with Laravel packages like stancl/tenancy supporting this setup very well.

**Avantages** : Isolation complète, scalabilité, backup/restore par tenant
**Inconvénients** : Consommation ressources, complexité déploiement

### Popular Laravel Multi-Tenancy Packages

**stancl/tenancy** ⭐ **Le plus complet**
stancl/tenancy automatically switches database connections and all other things in the background, letting you leverage standard Laravel code into a full SaaS application. This package has the most features out of all multi-tenancy packages for Laravel.

**Features** :
- Automatic tenant identification (domain/subdomain)
- Database switching automatique
- Tenant-aware queue jobs
- Central domain + tenant domains
- Tenant provisioning commands

### SaaS Starter Kits for 2025

- **SaaSykit Tenancy** : A great choice for SaaS developers who want to build a feature-rich multi-tenant SaaS application while saving their time and effort

- **Tenancy for Laravel Boilerplate** : An application skeleton on top of which you can build your multi-tenant SaaS, including tenant registration, "your application is being created" message, logging in to tenant applications from central domain using email and much more

### Key Features & Best Practices

**Security** :
- Always validate user-tenant relationships to prevent cross-access
- Use API tokens with tenant context
- Implement encryption for sensitive data

**Billing Integration** :
- Integrate payment gateways (Stripe, Paddle, Razorpay) with subscription logic in Laravel
- Offer different plans and seat-based pricing

**API Design** :
- Prefix routes with `/api/{tenant_id}` or use subdomains like `tenant1.app.com`
- Implement middleware for context switching

### Benefits

- **Reduced operational costs** through shared infrastructure
- **Simplified maintenance** where one update benefits all tenants
- **Faster onboarding** with instant tenant setup and management

### Sources

- [Tenancy for Laravel](https://tenancyforlaravel.com/)
- [Multi-tenant SaaS boilerplate for Laravel](https://tenancyforlaravel.com/saas-boilerplate/)
- [Best Laravel Multi-Tenant SaaS Starter Kits for 2025 | SaaSykit](https://saasykit.com/blog/best-laravel-multi-tenant-saas-starter-kits-for-2025)
- [Unlock Multi-Tenancy in Laravel | Expert Laravel](https://expertlaravel.com/unlock-multi-tenancy-in-laravel-to-build-scalable-saas-apps/)
- [The multi tenant saas toolkit for Laravel](https://tenancy.dev/)
- [Building Multi-Tenant SaaS Applications Using Laravel + React in 2025 | Cloudexis](https://cloudexistechnolabs.com/building-multi-tenant-saas-applications-using-laravel-react-in-2025/)
- [Implementing Multi-Tenant Architecture in Laravel | Medium](https://medium.com/@dev.muhammadazeem/%EF%B8%8F-implementing-multi-tenant-architecture-in-laravel-the-right-way-8518283c108c)
- [How to Build a Secure Multi-Tenant Application in Laravel 2025 | Arudhra IT](https://arudhraittechs.org/how-build-secure-multi-tenant-application-laravel-2025-edition)
- [Building Laravel Multi-Tenant App | BytesBrothers](https://www.bytesbrothers.com/blog/building-laravel-multi-tenant-app-architecture-tenant-isolation/)
- [Multi-tenant SaaS boilerplate for Laravel | Laravel News](https://laravel-news.com/multi-tenant-saas-boilerplate-for-laravel)

---

## 5. PHP 8.2 Features & Best Practices

### Key PHP 8.2 Features

#### 1. Readonly Classes
PHP 8.2 adds support to declare the entire class as readonly, meaning all its properties will automatically inherit the readonly feature. This feature helps in making your codebase consistent, plus provides readonly classes which enforce strict typing.

**Exemple** :
```php
readonly class User
{
    public function __construct(
        public string $name,
        public string $email,
        public DateTime $createdAt
    ) {}
}
```

#### 2. Enhanced Type System
PHP 8.2 enhances the type system by allowing the usage of `null`, `true`, and `false` as standalone types, providing more precise type declarations for function parameters and return values.

**Exemple** :
```php
function isValid(): true|false|null
{
    // Return null if unknown, true if valid, false if invalid
}
```

#### 3. New Random Extension
PHP 8.2 introduces a new PHP extension named random, which organizes and consolidates existing PHP functionality related to random number generation and introduces PHP class structures and exception classes.

#### 4. SensitiveParameter Attribute
It is now possible to mark sensitive parameters with a PHP attribute named `SensitiveParameter`, which makes PHP redact the sensitive information from the stack trace, improving security when handling passwords and other sensitive data.

**Exemple** :
```php
function login(
    string $username,
    #[\SensitiveParameter] string $password
) {
    // Password won't appear in stack traces
}
```

#### 5. Disjunctive Normal Form (DNF) Types
Another new feature is Disjunctive Normal Form (DNF), which allows for a standard way to write combined Union and Intersection types.

**Exemple** :
```php
function process((Stringable&Countable)|array $data): void
{
    // Accept objects that are both Stringable AND Countable, OR arrays
}
```

### Best Practices for 2025

**Use Latest Stable PHP Version**
Using the latest stable PHP version (PHP 8.2 as of 2025) ensures access to the newest features, security patches, and performance enhancements.

**Enable JIT for Complex Computations**
If your application involves complex computations, enabling JIT compilation can significantly enhance speed, however, for basic web applications, the impact may be minimal.

**Use Union Types**
Using union types where applicable can simplify function definitions and reduce unnecessary validation checks.

**Utilize Nullsafe Operator**
Instead of writing multiple conditional checks for null values, utilize the nullsafe operator to streamline and simplify your code.

**Exemple** :
```php
// Before
$country = null;
if ($user !== null && $user->getAddress() !== null) {
    $country = $user->getAddress()->getCountry();
}

// After (PHP 8.0+)
$country = $user?->getAddress()?->getCountry();
```

**Constructor Property Promotion**
Constructor property promotion streamlines class definitions by allowing properties to be declared and initialized in the constructor signature, which eliminates boilerplate code and increases readability, especially for DTOs (Data Transfer Objects).

**Exemple** :
```php
// Before
class User
{
    private string $name;
    private string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}

// After (PHP 8.0+)
class User
{
    public function __construct(
        private string $name,
        private string $email
    ) {}
}
```

**Automate Deployments & Monitor Health**
Automate deployments via CI/CD pipelines, ensuring rapid, reliable, and repeatable deployment cycles, and track application health with Prometheus, Grafana, or Datadog, monitoring metrics such as request latency, error rates, and server resource utilization.

### Sources

- [PHP 8.x Features You Should Be Using in 2025 | DEV Community](https://dev.to/patoliyainfotech/php-8x-features-you-should-be-using-in-2025-5145)
- [What's New in PHP 8.2 | Kinsta](https://kinsta.com/blog/php-8-2/)
- [What's New in PHP 8 | Kinsta](https://kinsta.com/blog/php-8/)
- [Boosting Web Development with PHP 8 | DEV Community](https://dev.to/eminencetech/boosting-web-development-with-php-8-features-best-practices-68k)
- [Best Practices for PHP environments in 2025 | UMA Technology](https://umatechnology.org/best-practices-for-php-environments-in-2025/)
- [PHP 8.2 Latest Features and Updates | Clariontech](https://www.clariontech.com/blog/latest-features-and-updates-of-php-8.2)
- [What's new in PHP 8 | Stitcher.io](https://stitcher.io/blog/new-in-php-8)
- [PHP 8.5: New Features and Deprecations | Zend](https://www.zend.com/blog/php-8-5-features)
- [PHP 8.2 features and changes | Upsun](https://upsun.com/blog/php-8-2-features-and-changes/)
- [PHP: The Right Way](https://phptherightway.com/)

---

## 6. PHP 8.2 Performance Optimization

### Readonly Properties Performance Benefits

Readonly properties allow developers to avoid the performance overhead associated with mutable state management. By enforcing immutability, reasoning about the behavior of objects in a codebase becomes simpler, helping to minimize bugs and improve code maintainability, contributing to more stable and performant web applications.

### PHP 8.2 Readonly Classes

Readonly properties were introduced in PHP 8.1, and PHP 8.2 builds on top of them by adding syntactic sugar to make all class properties readonly at once. This is especially useful when you're using data transfer objects or value objects, where a class only has public readonly properties.

**Exemple** :
```php
readonly class UserDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public DateTime $createdAt
    ) {}
}
```

### PHP 8.2 Performance Improvements

PHP 8.2 includes:
- **Reduced memory footprint** of strings returned by various functions
- **Initial support for JIT performance profiling** generation for macOS Instrument
- **Allocating the JIT buffer** Opcache close to the PHP .text segment to allow using direct IP-relative calls and jumps

### 2025 Trends

Performance optimization remains a top priority in 2025, with developers turning to tools like:
- **PHP-FPM** : FastCGI Process Manager
- **OPcache** : Opcode cache (must-have)
- **Redis** : In-memory data store

**Techniques** :
- Lazy loading
- Code splitting
- Asynchronous processing (queues)

### Sources

- [PHP 8.1: readonly properties | PHP.net](https://www.php.net/releases/8.1/en.php)
- [What's new in PHP 8.2 | Stitcher.io](https://stitcher.io/blog/new-in-php-82)
- [Readonly classes in PHP 8.2 | Stitcher.io](https://stitcher.io/blog/readonly-classes-in-php-82)
- [PHP 8.x: Performance Improvement Features | Accesto Blog](https://accesto.com/blog/php-performance-improvement-features/)
- [PHP 8.4 Features | Appeak Technologies](https://www.appeaktech.com/php-8-4-features-whats-new-and-what-developers-need-to-know/)
- [PHP 8.2 Released With Readonly Classes | Phoronix](https://www.phoronix.com/news/PHP-8.2-Released)
- [PHP Development Trends in 2025 | Toxigon](https://toxigon.com/php-development-trends-in-2025)
- [PHP 8.1: readonly properties | Stitcher.io](https://stitcher.io/blog/php-81-readonly-properties)
- [Readonly Properties | PHP.Watch](https://php.watch/versions/8.1/readonly)
- [Unlocking PHP 8.3 | IPC Blog](https://phpconference.com/blog/php-8-3-new-features-enhancements-guide/)

---

## 7. MySQL 8 Indexing Strategies

### Core Indexing Principles

The best way to improve the performance of SELECT operations is to create indexes on one or more of the columns that are tested in the query, with index entries acting like pointers to table rows that allow queries to quickly determine which rows match a condition in the WHERE clause.

**However**, unnecessary indexes waste space and time for MySQL to determine which indexes to use, and indexes also add to the cost of inserts, updates, and deletes because each index must be updated, requiring the right balance to achieve fast queries.

### Advanced MySQL 8 Features

#### 1. Invisible Indexes ⭐ **Nouveau MySQL 8**

An invisible index is an index that exists on disk and is maintained (updated on writes) but is ignored by the MySQL optimizer by default. This allows you to monitor your application's performance without the index being used by queries, and if no regressions occur after a period, you can confidently drop it, while if performance degrades, a quick `ALTER INDEX … VISIBLE` reverts the change instantly.

**Exemple** :
```sql
-- Créer index invisible
CREATE INDEX idx_email ON users(email) INVISIBLE;

-- Tester performance sans l'index
-- Si OK, supprimer définitivement
DROP INDEX idx_email ON users;

-- Si performance dégradée, rendre visible
ALTER INDEX idx_email ON users VISIBLE;
```

#### 2. Descending Indexes ⭐ **Nouveau MySQL 8**

In MySQL 8, descending indexes are supported, so we can create descending indexes to optimize queries with descending ORDER BY clauses.

**Exemple** :
```sql
CREATE INDEX idx_created_desc ON posts(created_at DESC);

-- Optimise cette requête
SELECT * FROM posts ORDER BY created_at DESC LIMIT 10;
```

### Monitoring and Optimization

Regularly monitor the usage and performance of indexes using MySQL's built-in monitoring tools such as the Performance Schema and the slow query log to identify underutilized indexes, unused indexes, and indexes causing performance bottlenecks.

MySQL indexing is not a one-time process, and it is advised to conduct weekly or monthly checks of database performance to prevent issues from adversely affecting your applications.

**Requêtes utiles** :
```sql
-- Trouver indexes non utilisés
SELECT * FROM sys.schema_unused_indexes;

-- Tables sans index
SELECT * FROM sys.schema_tables_with_full_table_scans;
```

### Common Pitfalls

Every time you do an INSERT, UPDATE, or DELETE query for a row, MySQL must update every affected index, which basically means: **More indexes = more disk I/O and CPU usage**.

**Best practices** :
- ❌ Avoid indexing columns with large text or binary data types unless necessary
- ✅ Index columns used in WHERE, JOIN, ORDER BY
- ✅ Use composite indexes for multi-column searches
- ✅ Monitor index cardinality (selectivity)

**Exemple composite index** :
```sql
-- Pour cette requête
SELECT * FROM esbtp_etudiants
WHERE status = 'actif'
  AND annee_academique = '2024-2025'
ORDER BY nom;

-- Créer index composite
CREATE INDEX idx_status_annee_nom
ON esbtp_etudiants(status, annee_academique, nom);
```

### Sources

- [MySQL Indexing Best Practices: A Comprehensive Guide | Medium](https://akhil-mathew.medium.com/mysql-indexing-best-practices-a-comprehensive-guide-15dc0f9b0442)
- [MySQL Indexing Best Practices | GeeksforGeeks](https://www.geeksforgeeks.org/mysql/mysql-indexing-best-practices/)
- [MySQL 8.4 Reference Manual :: Optimization and Indexes](https://dev.mysql.com/doc/refman/8.4/en/optimization-indexes.html)
- [Understanding MySQL Indexes: Types, Benefits, and Best Practices | Percona](https://www.percona.com/blog/understanding-mysql-indexes-types-best-practices/)
- [MySQL indexing - what are the best practices? | Medium](https://akhil-mathew.medium.com/mysql-indexing-best-practices-779282b0995b)
- [MySQL 8.0 Reference Manual :: Optimization and Indexes](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)
- [The hidden cost of too many indexes in MySQL | Pythian](https://www.pythian.com/blog/technical-track/the-hidden-cost-of-too-many-indexes-in-mysql)
- [Deep Dive into MySQL Indexing Strategies | Alibaba Cloud](https://www.alibabacloud.com/blog/deep-dive-into-mysql-indexing-strategies_601595)
- [Optimizing MySQL Indexing | Pulse Solutions](https://www.pulsesolutions.com/development/optimizing-mysql-indexing-best-practices-and-strategies-for-complex-queries/)
- [MySQL 8.4 Indexing Explained | Genexdbs](https://genexdbs.com/mysql-8-4-indexing-explained-how-to-use-invisible-functional-descending-indexes/)

---

## 8. MySQL 8 JSON Optimization

### Key Optimization Techniques

#### 1. Generated Columns with Indexing ⭐ **Recommandé**

You can create indexes on JSON columns using virtual columns, which improves query performance on JSON data. This is the most common approach.

**Exemple** :
```sql
-- Modèle ESBTPBulletin avec JSON professeurs
ALTER TABLE esbtp_bulletins
ADD COLUMN professeurs_count INT GENERATED ALWAYS AS (
    JSON_LENGTH(professeurs)
) STORED,
ADD INDEX idx_professeurs_count (professeurs_count);

-- Recherche rapide bulletins avec beaucoup de profs
SELECT * FROM esbtp_bulletins
WHERE professeurs_count > 5;
```

**Exemple Laravel** :
```sql
ALTER TABLE esbtp_planifications_academiques
ADD COLUMN filiere_code VARCHAR(50) AS (
    matiere_details->>'$.filiere_code'
) VIRTUAL,
ADD INDEX idx_filiere_code (filiere_code);
```

#### 2. Multi-Valued Indexes (MySQL 8.0.17+) ⭐ **Nouveau**

Multi-value indexing was introduced in MySQL 8.0.17 and allows creating an index in the InnoDB storage engine to efficiently query columns that store array values.

**Exemple** :
```sql
-- Table avec array JSON
CREATE TABLE classes (
    id INT PRIMARY KEY,
    etudiants JSON,
    INDEX idx_etudiants ((CAST(etudiants AS UNSIGNED ARRAY)))
);

-- Recherche rapide dans array
SELECT * FROM classes
WHERE JSON_CONTAINS(etudiants, '123', '$');
```

#### 3. Functional Indexes (MySQL 8.0.13+)

Beginning with MySQL 8.0.13, you can skip the intermediate step of creating a generated column and create a "functional index," which is an index on an expression rather than a column.

**Exemple** :
```sql
-- Index sur expression JSON directement
CREATE INDEX idx_bulletin_moyenne
ON esbtp_bulletins ((JSON_EXTRACT(notes, '$.moyenne_generale')));

-- Requête optimisée
SELECT * FROM esbtp_bulletins
WHERE JSON_EXTRACT(notes, '$.moyenne_generale') >= 10;
```

#### 4. Binary Storage Format

MySQL 8.0 optimizes the storage of JSON data using a binary format that allows for faster reading and parsing, enabling the server to directly access nested values or child objects using keys or array indices without having to read or parse all values in the document first.

#### 5. Optimizer Recognition

The MySQL optimizer looks for compatible indexes on virtual columns that match JSON expressions.

**Exemple** :
```sql
-- Query automatically uses idx_filiere_code created above
SELECT * FROM esbtp_planifications_academiques
WHERE matiere_details->>'$.filiere_code' = 'GBTP';
```

### Sources

- [Optimizing JSON Queries with Advanced Indexing in MySQL 8.0 | Medium](https://medium.com/chat2db/optimizing-json-queries-with-advanced-indexing-in-mysql-8-0-392f2fdfd842)
- [MySQL JSON Guide | DBTech](https://www.dbtech.digibeatrix.com/en/mysql/data-types/mysql-json-guide/)
- [Increasing MySQL JSON Query Performance | Emincan Özcan](https://emincanozcan.com/blog/mysql-json-index-and-laravel)
- [MySQL 8: Advanced JSON Features | Medium](https://medium.com/@mydbopsdatabasemanagement/mysql-8-advanced-json-features-for-better-data-management-29e3cce9ab34)
- [MySQL 8.0 Reference Manual :: JSON Data Type](https://dev.mysql.com/doc/refman/8.0/en/json.html)
- [Using JSON Data Types in MySQL | Developers Heaven](https://developers-heaven.net/blog/using-json-data-types-in-mysql-storing-and-querying-unstructured-data/)
- [JSON Data Improvements in MySQL 8.0 | MySQL](https://downloads.mysql.com/events/mysql-summit-2023/Oracle_MySQL_Summit_2023_JSON.pdf)
- [Indexing JSON in MySQL | PlanetScale](https://planetscale.com/blog/indexing-json-in-mysql)
- [Has MySQL 8 improved on JSON field support? | Quora](https://www.quora.com/Has-MySQL-8-improved-on-JSON-field-support-How-is-it-compared-to-PostgreSQL-especially-on-query-performance)
- [JSON and Generated Columns | Unofficial MySQL 8.0 Guide](http://www.unofficialmysqlguide.com/json.html)

---

## 9. Spatie Laravel Permission

### Latest Version

The latest version is **6.24.0**, released on December 13, 2025, and it supports **PHP ^8.0** and **Laravel versions 8.12 through 12.0**.

### Key Best Practices

#### 1. Use Model Policies for Access Control ⭐ **Recommandé**

The best way to incorporate access control for application features is with **Laravel's Model Policies**. Using Policies allows you to simplify things by abstracting your "control" rules into one place, where your application logic can be combined with your permission rules.

**Exemple** :
```php
// app/Policies/InscriptionPolicy.php
class InscriptionPolicy
{
    public function update(User $user, Inscription $inscription)
    {
        return $user->can('edit inscriptions')
            && $inscription->status === 'brouillon';
    }
}
```

#### 2. Check Permissions, Not Roles ⭐ **Important**

You should always use the native Laravel `@can` and `can()` directives everywhere in your app, and it's safer to have your Views test `@can('view member addresses')` or `@can('edit document')`, **INSTEAD** of testing for `$user->hasRole('Editor')`.

**Pourquoi ?** Les rôles peuvent changer, mais les permissions restent stables.

**Exemple Blade** :
```blade
{{-- ✅ BON --}}
@can('edit inscriptions')
    <a href="{{ route('inscriptions.edit', $inscription) }}">Modifier</a>
@endcan

{{-- ❌ MAUVAIS --}}
@if($user->hasRole('admin'))
    <a href="{{ route('inscriptions.edit', $inscription) }}">Modifier</a>
@endif
```

#### 3. Permission Design Strategy

You can treat permission names as **static** (only editable by developers), and then your application (almost) never needs to know anything about role names, so you could (almost) change role names at will.

**Convention KLASSCI** :
- `view inscriptions`
- `create inscriptions`
- `edit inscriptions`
- `delete inscriptions`
- `validate inscriptions`

#### 4. Performance Optimization

If your app is deleting and adding new permissions frequently, you may find that things are more performant if you lookup the permission and assign it to the role, like: `$permission->assignRole($role)`.

**Exemple** :
```php
// ✅ Plus performant si permissions changent souvent
$permission = Permission::findByName('edit inscriptions');
$permission->assignRole('admin');

// Au lieu de
$role = Role::findByName('admin');
$role->givePermissionTo('edit inscriptions');
```

#### 5. Middleware Registration (Laravel 11+)

In Laravel 11+ open `/bootstrap/app.php` and register middleware there using the `withMiddleware()` method.

**Exemple** :
```php
// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->create();
```

### Sources

- [GitHub - spatie/laravel-permission](https://github.com/spatie/laravel-permission)
- [Introduction | laravel-permission | Spatie](https://spatie.be/docs/laravel-permission/v6/introduction)
- [spatie/laravel-permission Guide 2025 | Generalist Programmer](https://generalistprogrammer.com/tutorials/spatie-laravel-permission-composer-package-guide)
- [Middleware | laravel-permission | Spatie](https://spatie.be/docs/laravel-permission/v6/basic-usage/middleware)
- [Model Policies | laravel-permission | Spatie](https://spatie.be/docs/laravel-permission/v6/best-practices/using-policies)
- [Using Policies | GitHub](https://github.com/spatie/laravel-permission/blob/main/docs/best-practices/using-policies.md)
- [Installation in Laravel | Spatie](https://spatie.be/docs/laravel-permission/v6/installation-laravel)
- [spatie/laravel-permission | Packagist](https://packagist.org/packages/spatie/laravel-permission)
- [Roles vs Permissions | GitHub](https://github.com/spatie/laravel-permission/blob/main/docs/best-practices/roles-vs-permissions.md)
- [Performance | GitHub](https://github.com/spatie/laravel-permission/blob/main/docs/best-practices/performance.md)

---

## 10. Blade Templates Optimization

### Performance Fundamentals

Blade templates are compiled into plain PHP code and cached until they are modified, meaning Blade adds essentially **zero overhead** to your application. Unlike traditional PHP templates, Blade templates are compiled into plain PHP code and cached for optimal performance.

### Key Optimization Strategies

#### 1. Minimize Template Logic

Complex logic and heavy computations in Blade templates can impact rendering speed, so minimize Blade logic by moving complex operations to controllers or services.

**Exemple** :
```blade
{{-- ❌ MAUVAIS - Logique complexe dans Blade --}}
@foreach($etudiants as $etudiant)
    @php
        $moyenne = 0;
        foreach($etudiant->notes as $note) {
            $moyenne += $note->valeur * $note->coefficient;
        }
        $moyenne = $moyenne / $totalCoefficients;
    @endphp
    <td>{{ number_format($moyenne, 2) }}</td>
@endforeach

{{-- ✅ BON - Calcul dans Controller/Service --}}
@foreach($etudiants as $etudiant)
    <td>{{ number_format($etudiant->moyenne_generale, 2) }}</td>
@endforeach
```

#### 2. Use Partial Views

Break down lengthy and complex Blade templates into smaller, reusable partials to improve organization.

**Exemple** :
```blade
{{-- resources/views/esbtp/inscriptions/show.blade.php --}}
<div class="header">
    @include('esbtp.inscriptions.partials.header')
</div>

<div class="workflow">
    @include('esbtp.inscriptions.partials.workflow')
</div>

<div class="paiements">
    @include('esbtp.inscriptions.partials.paiements')
</div>
```

#### 3. Laravel Octane for Memory Caching ⭐ **Avancé**

You can set up a static array to cache rendered Laravel Blade templates to increase the performance of a basic Laravel application by serving cached templates directly from memory when using Laravel Octane with RoadRunner.

**Performance** : Jusqu'à **10x plus rapide** pour pages statiques.

### Laravel 12 Improvements

Laravel 12 brings significant improvements to the Blade template engine, most notably replacing the traditional sprintf approach with powerful inline components. Laravel 12's Blade engine compiles components more efficiently than string formatting operations, resulting in faster page loads and reduced server load.

### Best Practices

**For SEO-heavy pages**, combine optimized Blade with pre-rendering strategies.

**When rendering lists**, generate minimal markup to reduce client-side parsing.

**Exemple** :
```blade
{{-- ✅ BON - Markup minimal --}}
<ul>
@foreach($classes as $classe)
    <li data-id="{{ $classe->id }}">{{ $classe->nom }}</li>
@endforeach
</ul>

{{-- ❌ MAUVAIS - Markup trop verbeux --}}
<ul>
@foreach($classes as $classe)
    <li class="list-item" data-id="{{ $classe->id }}" data-status="{{ $classe->status }}">
        <div class="item-wrapper">
            <span class="item-name">{{ $classe->nom }}</span>
        </div>
    </li>
@endforeach
</ul>
```

### Sources

- [Blade Optimization Guide for Laravel Developers | iFlair](https://www.iflair.com/blade-template-optimization-for-laravel-developers/)
- [Blade Templates - Laravel 12.x](https://laravel.com/docs/12.x/blade)
- [Laravel 12 Blade Template Engine Upgrades | Markaicode](https://markaicode.com/laravel-12-blade-template-engine-upgrades-sprintf-inline-components/)
- [Laravel Blade Basics | InMotion Hosting](https://www.inmotionhosting.com/support/edu/laravel/laravel-blade-basics/)
- [Cache Laravel Blade Templates in Memory with Laravel Octane](https://oliverlundquist.com/2025/06/29/caching-blade-in-memory-with-laravel-octane.html)
- [Twig vs Blade | DistantJob](https://distantjob.com/blog/twig-vs-blade/)
- [Laravel Blade Template Engine: A Beginner's Guide | SSHOcean](https://sshocean.net/posts/laravel-blade-template-engine-a-beginners-guide)
- [Laravel Blade Tips & Tricks | Lexo](https://www.lexo.ch/blog/2025/10/blade-tips-tricks-that-save-you-time-laravel-blade-essentials/)
- [Mastering Laravel Blade | Medium](https://medium.com/@nethmiwelgamvila/mastering-laravel-blade-why-developers-love-it-and-whats-new-in-laravel-11-2ad0666c4562)
- [Mastering Laravel Performance Optimization | Medium](https://medium.com/@satyamv57/mastering-laravel-performance-optimization-a-comprehensive-guide-deae2bc50054)

---

## 11. Alpine.js Laravel Integration

### Alpine AJAX Plugin

**Alpine AJAX** is a small Alpine.js plugin that provides an easy way to make AJAX requests and render content on the page. This is a popular third-party solution specifically designed for this use case.

**Installation** :
```bash
npm install alpine-ajax
```

**Documentation** : [https://alpine-ajax.js.org/](https://alpine-ajax.js.org/)

### Best Practices for 2025

#### 1. Progressive Enhancement ⭐ **Important**

It's good practice to start building your UI without Alpine AJAX, make your entire website work as it would if Alpine AJAX were not available, then sprinkle in AJAX functionality at the end, which will ensure that your AJAX interactions degrade gracefully when JavaScript is not available.

**Exemple** :
```blade
{{-- Fonctionne SANS JavaScript (form submit normal) --}}
<form action="{{ route('inscriptions.search') }}" method="GET"
      x-target="results"  {{-- Alpine AJAX si disponible --}}
>
    <input type="text" name="q" placeholder="Rechercher...">
    <button type="submit">Rechercher</button>
</form>

<div id="results">
    {{-- Résultats affichés ici (server-side ou AJAX) --}}
    @include('partials.results')
</div>
```

#### 2. Implementation Approach

`x-target="contacts"` on the search form changes the behavior of the form: When it is submitted an AJAX request is issued and the updated content returned in the response replaces the existing content on the page, and `@input.debounce` on the search input automatically submits the search form when the value of the input is changed.

**Exemple complet** :
```blade
<div x-data="{ search: '' }">
    <form action="{{ route('etudiants.search') }}"
          method="GET"
          x-target="etudiants-list"
          @submit.prevent="$ajax()">

        <input type="text"
               name="q"
               x-model="search"
               @input.debounce.500ms="$el.form.requestSubmit()"
               placeholder="Rechercher étudiant...">
    </form>

    <div id="etudiants-list">
        @include('partials.etudiants-list')
    </div>
</div>
```

#### 3. Integration Benefits

When combined with Alpine.js, Laravel can provide dynamic, real-time interactivity on the frontend while handling complex logic on the server side, and with Alpine.js, you don't need a complex frontend framework like Vue or React.

**Avantages** :
- ✅ **Léger** : ~15kb minified (vs 40kb+ pour Vue/React)
- ✅ **Pas de build step** : Fonctionne directement dans Blade
- ✅ **Progressive enhancement** : Fonctionne sans JS
- ✅ **Laravel-first** : Conçu pour server-side rendering

#### 4. General Best Practices

**Keep components small and focused**
```blade
{{-- ✅ BON - Component simple, focused --}}
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

**Use x-init for initialization logic**
```blade
<div x-data="{ count: 0 }"
     x-init="count = {{ $initialCount }}">
    Compteur: <span x-text="count"></span>
</div>
```

**Combine with Livewire for complex operations**
```blade
{{-- Simple interactivity: Alpine.js --}}
<div x-data="{ show: false }">...</div>

{{-- Complex CRUD operations: Livewire --}}
<livewire:inscription-create />
```

**Alpine.js shines when used for simple interactivity**

### Sources

- [Reference | Alpine AJAX](https://alpine-ajax.js.org/reference/)
- [Build Instant Search with Alpine AJAX & Laravel | Imacrayon](https://imacrayon.com/words/instant-search-with-alpine-ajax-and-laravel/)
- [Building Dynamic Frontend Applications with Laravel and Alpine.js | Magecomp](https://magecomp.com/blog/building-dynamic-frontend-applications-with-laravel-and-alpine-js/)
- [Add Alpine.js to any Laravel project (2025) | Benjamin Crozat](https://benjamincrozat.com/alpine-js-laravel)
- [will x-ajax be added? | GitHub Discussion](https://github.com/alpinejs/alpine/discussions/2405)
- [My Journey Learning Alpine.js, HTMX, and AJAX | Medium](https://medium.com/@thumijosphat47/from-functional-to-delightful-my-journey-learning-alpine-js-htmx-and-ajax-65c10424a949)
- [Let's build an ajax form with Alpine.js | dberri.com](https://dberri.com/lets-build-an-ajax-form-with-alpine-js/)
- [Alpine.js Tips & Tricks for Laravel Developers | DEV Community](https://dev.to/harold_defree/alpinejs-tips-tricks-for-and-from-and-entry-level-laravel-developers-213c)
- [Alpine.js: a lightweight framework | Benjamin Crozat](https://benjamincrozat.com/alpine-js)
- [AlpineJS | Laravel Livewire](https://laravel-livewire.com/docs/2.x/alpine-js)

---

## 📊 Résumé Best Practices 2025

### Laravel 10
- ✅ Fat Models, Skinny Controllers
- ✅ Service Layer Architecture (MVCS)
- ✅ Eager Loading (prevent N+1)
- ✅ Use Eloquent over Query Builder
- ✅ Implement automated testing
- ✅ Follow PSR-12 + Laravel conventions

### PHP 8.2
- ✅ Use readonly classes for DTOs
- ✅ Constructor property promotion
- ✅ Nullsafe operator `?->`
- ✅ Union types
- ✅ `#[SensitiveParameter]` attribute
- ✅ Enable OPcache + JIT (si applicable)

### MySQL 8
- ✅ Use invisible indexes for testing
- ✅ Descending indexes for `ORDER BY DESC`
- ✅ Generated columns for JSON indexing
- ✅ Multi-valued indexes for JSON arrays
- ✅ Monitor index usage (Performance Schema)
- ✅ Balance: too few = slow queries, too many = slow writes

### Packages
- ✅ **Spatie Permission** : Check permissions, not roles
- ✅ **Blade** : Minimize logic, use partials
- ✅ **Alpine.js** : Progressive enhancement, keep simple

### Multi-Tenancy
- ✅ Use `stancl/tenancy` (most complete package)
- ✅ Database-per-tenant (best isolation)
- ✅ Implement security validation (tenant context)
- ✅ Use SaaS starter kits (SaaSykit, Tenancy for Laravel)

---

*Document créé : 17 décembre 2024*
*Recherches effectuées : 11 sources web principales + 100+ articles référencés*
*Utilisation : Alimenter BEST_PRACTICES.md KLASSCI (Phase 3)*
