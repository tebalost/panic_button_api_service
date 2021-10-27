# README #

This is the implementation of the panic-api for login, create users, accept panic  report on all requests made by users.

### What is this repository for? ###

* a piece of a solution to process panic requests and report  on them
* Version 1.0.0

### Functions for Demo/MVP1 and MVP2###

* MVP1 - login
* MVP1 - create-update-user
* MVP1 - panic-request
* MVP1 - get-panic-requests

* MVP2 - push-notifactions to providers
* MVP2 - providers-accept-request

### Components ###

* JWT (Token Generation and Validation for authentication)
* MySQL Database Connection (Storing and Retrieving Data)
* .htaccess to beautify the URI's for the requests

### All Requests are below (For Demo/MVP1) ###

* api/auth/login
* api/customer/create-update-user
* api/customer/panic-request
* api/customer/get-panic-requests