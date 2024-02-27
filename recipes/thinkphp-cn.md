# Thinkphp 使用 Adapterman

[English](./thinkphp.md) | 中文

```shell
# 安装 adapterman 到你的项目
composer require joanhey/adapterman
# 启动
./vendor/bin/adapterman start
```
运行逻辑解释:  
1.`./vendor/bin/adapterman start` 命令其实是执行了 

    /usr/bin/env php -c vendor/joanhey/adapterman/cli-php.ini vendor/joanhey/adapterman/src/start.php "$@"

2.php -c [vendor/joanhey/adapterman/cli-php.ini](https://github.com/joanhey/AdapterMan/blob/master/cli-php.ini) 将一些php内置函数禁用之后,adapterman框架实现这些禁用的函数,实现在fpm下的功能在 php cli 下正常运行  

3.[vendor/joanhey/adapterman/src/start.php](https://github.com/joanhey/AdapterMan/blob/master/src/start.php) 文件将自动启动服务器，并自动检测正在使用的框架.  
其中 [vendor/joanhey/adapterman/src/frameworks/index.php](https://github.com/joanhey/AdapterMan/blob/master/src/frameworks/index.php) 检测正在使用的框架

在浏览器访问

```http://localhost:8080```


或者使用