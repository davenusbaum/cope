# Cope - Command Oriented PHP Environment

Cope is a minimalist PHP framework inspired by Apache Struts.

## History
The Cope framework was created in 2004 when we wanted an MVC framework
for a new web application.
This was before virtual machines were commonly available, so we were working
in shared hosting with PHP 4.
We pulled the methods we needed from the Servlet API and made them into PHP 
functions (no objects in PHP 4).
Struts actions became commands, we even kept the .do extension.
The application we were building was multi-tenant, so we baked the tenant
identifiers into our url scheme `https://<account slug>/<command scope>/<command>.do`.
There is nothing RESTful about this, but routing is extremely fast.

## Evolution
Cope provides a fast and easily extensible framework for our solutions, so
there has never been a push to move to another framework.
We have, however, updated the framework as PHP evolved.

