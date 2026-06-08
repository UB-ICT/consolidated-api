<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/UB-ICT/consolidated-api/actions/workflows/tests.yml"><img src="https://github.com/UB-ICT/consolidated-api/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
<a href="https://github.com/UB-ICT/consolidated-api/actions/workflows/tests.yml?query=branch%3Adev"><img src="https://github.com/UB-ICT/consolidated-api/actions/workflows/tests.yml/badge.svg?branch=dev" alt="Tests (dev)"></a>
</p>

## Public Safety API
Flow Diagram Description

1. User Creation and Role Assignment
    - A user is created in the user table.

    - The role_id field in the user table links the user to a specific role.
    
    - Based on the role, access_right defines what the user can do.


2. User and Campus Association

    - The user is assigned to one or more campuses via the user_campus table.

    - Each user can have a primary_campus for identification.


3. Menu and Role Access

    - Roles are linked to menus via the menu_role table, defining what menus are accessible to each role.

    - sub_menu links additional functionality to specific menus.

4. Message Flow

    - A user (identified by sender_id) sends a message.

    - The recipient table tracks which users receive the message.

    - The message_category table categorizes the message for filtering or prioritization.

5. Incident Reporting

    - A user uploads an incident_report tied to a specific campus and optionally a building.
    
    - The incident_file table stores file attachments related to the report.

    - The report status is tracked via the incident_status table.

    - The type of the incident is categorized using the incident_type table.

6. Departments and Members

    - Departments are created in the department table.

    - Users are associated with departments through the department_member composite table.



## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# UBPublicSafetyAdmindashboardBackend
# UBPublicSafetyAdmindashboardBackend
# backend
# backend


php artisan module:make-migration "add-google-id-to-users" Auth 