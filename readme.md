# KEPOO 

Kepoo is library to access your akademika data. We're not save your username or password, this library just act like interface between your application and akademika database.


## Installation
You can copy paste this library anywhere and `include` files. But since this library are using PSR-0, you can also get this library via packagist.

```
require: {
    "kepoo/akademika": "dev-master"
}
```


## Usage


```
$akademika = new Kepoo\Akademika;
$akademika->username = "YOUR_USERNAME";
$akademika->password = "YOUR_PASSWORD";
print_r($akademika->login());
print_r($akademika->sidebarMenu());
print_r($akademika->getTranscript());

```

## Note
