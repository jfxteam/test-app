# Test API

A simple API interface



## Installation

1. Clone the repository to your server

2. Define db entries in your ```config.php```. Example:

   ```php
   define('DB_USER','root');
   define('DB_PASS','');
   define('DB_HOST','localhost');
   define('DB_NAME','test_app');
   ```

3. Import `test_app.sql` to your database
4. Run your server.
5. Go to ```/``` to view todos list.



## Usage

#### Add todo

For adding todo use ```/api/todo/set``` with data:

```javascript
fetch('/api/todo/set', {
  method: 'POST',
  headers: {
      'Content-Type': 'application/json'
  },
  body: JSON.stringify({
      name: 'New todo',
      description: 'Todo description',
  })
})
.then(r => r.json())
```

#### Update todo

For adding todo use ```/api/todo/set``` with id and data:

```javascript
fetch('/api/todo/set', {
  method: 'POST',
  headers: {
      'Content-Type': 'application/json'
  },
  body: JSON.stringify({
      name: 'New todo',
      description: 'Todo description',
  })
})
.then(r => r.json())
```

#### Get todo

For adding todo use ```/api/todo/get``` with id:

```javascript
fetch('/api/todo/get', {
  method: 'POST',
  headers: {
      'Content-Type': 'application/json'
  },
  body: JSON.stringify({
      id: 2
  })
})
.then(r => r.json())
```

#### List todo

For getting the list of all todos use ```api/todo/list```:

```javas
fetch('/api/todo/list')
.then(r => r.json())
```



## System requirements

PHP 7.1 or higher, Apache 2.4