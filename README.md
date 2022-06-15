# Composer Mirror By Hyperf

 参考自阿里云 `golang `版本

## 环境要求

- PHP >= 8.0
- Swoole >= 4.8.0
- Hyperf >= 3.0

## 配置

 ```env
OSS_ACCESS_KEY_ID: "OSS AccessKeyID"
OSS_ACCESS_KEY_SECRET: "OSS AccessKeySecret"
OSS_ENDPOINT: "OSS endpoint, such as oss-cn-hangzhou.aliyuncs.com"
OSS_BUCKET: "Bucket"

# Unlimited to request dist
GITHUB_TOKEN: "GitHub token, such as: 6a023b828b17*****0ab5tgj6ddb3f0ccb3d30e0"

REPO_URL: "Synchronization source address, such as https://repo.packagist.org/
"
API_URL: "Change monitoring API address, such as: https://packagist.org/"
MIRROR_URL: "Mirror website, such as https://xxx.com"
DIST_URL: "Download address of zip package, such as https:/xxx.com/dists/"
PROVIDER_URL: "Provider prefix ， such as /"
```


## 运行

 ```shell
php bin/hyperf.php composer:sync
```

## 参考
* [Packagist](https://packagist.org/)
* [Composer](https://getcomposer.org)
* [PackagistMirror](https://github.com/aliyun/packagist-mirror)