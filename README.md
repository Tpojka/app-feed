<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

## About Feed Assignment

I decided to use Laravel framework because I am most familiar with it.
Regardless, I was thinking of generic concept how to make solution for this task so concept could be easily used/ported to other frameworks or languages.

I worked in Homestead environment and following are specification of system used on my local machine:

- Vagrant: 2.2.8 (latest is 2.2.10)
- Homestead image box: 9.5.1 (latest is 10.1.1)
- PHP: 7.4.9
- MySQL: 5.7.31-0ubuntu0.18.04.1 (Ubuntu)

Also, for better caching mechanism, memcached or redis should be installed on system where application is going to be used. 

###Solution description:
 
1.) MySQL Workbench is used to describe DB and relation between tables. EER diagram is stored in database/bckp/trivago_feed.png (related to root path) image.

2.) For relation scheme I decided to follow most of structure of `alexdebril/feed-io` package since it is mainly used and also there is package for Symfony that uses this package so porting to Symfony should be very easy. MySQL is used and tables are following:

- reader_results (has one feed)
- feeds (belongs to reader_result; has many categories; has many items)
- items (belongs to feed; has many categories; has one author; has many media; has many ratings)
- authors (belongs to item)
- media (belongs to item)
- categories [polymorphic] (has many feeds; has many items)
- ratings [polymorphic, generic, used from package]

3.) Laravel framework is used for making this application.

4.) Several tests are written to cover certain application code behavior and secure execution goes flawless.

5.) Heading to `[HTTP_HOST]/items` page, list of paginated items can be seen. Each item is presented with anchored title that redirects to original article page, description, author and date. If item has related media, first media image will be shown along.

6.) For rating system, `willvincent/laravel-rateable` package is used. It is also made another migration file that updates ratings table making `user_id` field optional. If this change wasn't made, package couldn't fulfill expectations without registered and logged user. For this task, as not proposed to have it, users table is not used. However if this package is ever going to be used in production, package itself is meant to be used with `users.user_id` related field for this polymorphic relation and any other use of it should be tweaked in similar way here is made. On items page, there is simple form for rating article. Output result will be ceiled by PHP_ROUND_HALF_UP integer. For this task, it is not limited amount of votes for one visitor. Although in production this should be under consideration whether rating should be allowed to all visitors or just registered/logged ones or per any other condition or rule. AJAX post request has been used for this feature.

7.) Page with form is accessible at `[HTTP_HOST]/items/create`. Form's action is `[HTTP_HOST]/items` and form's method is `POST`. Form has one input field and it is named `xml_link`. If submitting doesn't pass validation, page will be reloaded with output of validation errors. If form is submitted and data is correct, redirection will be made to `/items` page with new items (articles) shown from DB all together with old ones. 
 
8.) Several caching layers is used in application. There is used guzzle request cache to avoid unnecessary guzzle request calls so if result is found in cache storage, request won't be made but cached result will be rather used instead. Also, there is cached preview from DB results for items page. Each paginated page is stored under specific key. Memcached or Radis driver is used as caching storage engine.

9.) It is paid attention to error handling and validation of insert form.

###Installation

For having application works, either `memcached` either `radis` should be installed at server machine because this caching engine is used. I decided to use one of those because code that follows cache logic (exclusivelly caching logic related to DB cached results) is using cache tags which works with these type of caching engines. In other words if memcached or redis wouldn't be used, code would need some modification (i.e. where cache keys deliting is made).

After installing one of these packages, that caching driver should be set in .env file (it should work out of the box with default values).

Other values in `.env` file should be set as well. Those are `APP_URL`, `DB_*`.

`composer install` will bring all needed PHP packages.

`php artisan migrate` will install application tables. 
