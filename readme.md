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


### Initialization
```
$akademika = new Kepoo\Akademika;
$akademika->username = "YOUR_USERNAME";
$akademika->password = "YOUR_PASSWORD";

```

### Login
```
print_r($akademika->login());
```

### Get links
```
print_r($akademika->sidebarMenu());
```

### Get transcript
```
print_r($akademika->getTranscript());
```

### Get KRS
```
print_r($akademika->getKrs());
```

### Kepoo Indeks Prestasi Sementara Semester Lalu
I think this is what you want :v

```
$akademika->kepooTarget('niu')->getIps();
```


## Example
See `example.php`


## Note
Use this at your own risk. I'm not responsible to any kind of this software usage. We know that nothings secure, but this security hole is so classic. Even noob can do this easily. 

## Changelog
* 20-12-2014 [NEW] Initialization
* 21-12-2014 [FIXED] Move link crawler to separated function
* 21-12-2014 [NEW] Add niu data
* 21-12-2014 [NEW] capability to get IPS and KRS