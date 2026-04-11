# Rules and best practices
These rules I learned along the way from companies. It gives a little bit harmony and structure in our lifes and you will
not need to break your neck trying to read code. Because programming code is first for developers then for machines.

## Variables
```php
$euIds = [1, 2 , 6, 8]; //bad

$expiredUserIds = [1, 2 , 6, 8]; //good
//You must understand what variables are
```


## Please do not do "parkour", "hacky", "hacker", "10x developer programming"
### Example#1:
I do not want lose checks, only strong checks.
```php
//This is bad
if(empty($array))) {...}

//this is good
if(coun($array) === 0 || $array === []) {...}
```

### Example#2:
When it comes to big data or multi dimensional arrays it is better to return objects of array
```php
//This is bad
function getInformation(array $data): array 
{
    return ['level' => $data['level'] ?? null, 'name' => $data['surname'].randombytes_random16(), 'bio' => ['description' => $data['bio'] ?? null]];
}

//This is better
function getInformation(array $data): InformationDto 
{
    $informationDto = $this->informationParser->parse($data);  

    return $informationDto;;
}
```

### Example#3:
Simple arrays can be passed around but must be documented
```php

/**
 * @param User[] $users
 * 
 * @return int[]
 */
public function getIds(array $users): array
{
    // doing code and processing
    $ids = array_map(fn(User $user) => $user->getId(), $users);
    
    return $ids;
}
```

### Example#4:
When processing complex data, especially on loops, do not shove everything into single functions.  
```php
//This is bad
//somewhere in between code.
$names = array_map(fn(User $user) => $user->getName() ,array_filter(function(User $user) { if($user->isVerified()) return true; if($user->isBanned()) return false;}));

//Better - create separate method. And do your separate logic there
$name = $this->getNames($users);
//...
/**
 * @param User[] $users
 * 
 * @return string[]
 */
function getNames(array $users): array 
{
    $filteredNames = [];
    foreach($users as $user) {
        if(!$user->isVerified() || $user->isBanned()) {
            continue;
        }
       
       $filteredNames[] = $users->getName();
    }
    
    return $filteredNames;
}
```

### Example#5:
Let's use dedicated repositories instead of abstract type ones. There are already "abstract code" and a lot of json in code base so let's avoid that.
```php
//bad
$this->entityManager->getRepository(Email::class)->findForSending($limit);

//better
$this->emailRepository->findForSending($limit);
```

### Example#5:
Let's not overuse comments or let's use really minimal amount of comments. Because comments are noise. Programming code itself must be readable
and maintainable to the level where comments are not required. Programing code must read like a simple book. It explains itself.
```php
//THATS REALLY BAD

//We take find users which are not banned and which are validated when they got registered
$users = $this->userRepository->findBy(['validated' => $conditions->registeredAt->format(self::FORMAT), 'banned' => $condition->isBanned && str_contains('JoHon#123',$condition->username);
//Create shard for for users. ID-EMAIL-USERNAME
$shards = array_map(fn(User $user) => $suer->getId().'-'.$user->getEmail().'-'.$user->getUsername(),array_filter(fn(User $user) => $user->getEmail() && $user->getUsername && $user->getId(), $users));

//Better
$correctUsers = $this->userRepository->findCorrectUsers();
$userShards = $this->getShards($user);

/**
 * @param User[] $users
 * @return string[]
 */
function getShards(array $users): array {..}
```