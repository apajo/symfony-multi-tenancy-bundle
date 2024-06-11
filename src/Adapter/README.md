# Adapters (Symfony Multi Tenancy Bundle)

Adapters are responsible for dynamic configuration changes based on tenant table values at runtime.

## Built-in adapters

* __DatabaseAdapter__ - Changes system database connection configuration (DSN) value

* __FilesystemAdapter__ - Switched current Filesystem provider to a new Gaufrette FTP filesystem provider.

* __MailerAdapter__ - Updates symfony/mailer DSN value based on the tenant 