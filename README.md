# ThinkPHP

ThinkPHP3.2.5 修改版

## 修改说明

- 状态配置修改为在加载扩展配置之后加载
- C方法支持三维数组设置和获取支持
- U方法新增数组参数支持
- 自动添加数据库名特性
- SLOG自动初始化支持
- 支持指定JSONP方式返回数据
- 新增ajax返回附加数据
- MySQL断线自动重连
- I方法$name参数默认值设为''，支持解析POST请求的JSON数据
- URL参数绑定支持解析POST请求的JSON数据

## 环境变量配置说明

- 框架会自动加载根目录下.env文件，和TP5.1一样，提供env函数访问环境变量

## 命名空间说明

- 取消了类库 `.class.php` 后缀要求
- 新增根目录命名空间，即根目录为起始命名空间，如 `App\Controller` 即 `App/Controller.php` 文件

## 关联模型说明

    `Think\Model` 类新增with方法加载关联模型,支持一下用法.

    ```php
    // 使用模型文件里面relations方法获取预定义的关联模型配置
    BookModel::with(['author','comments'])->select();
    BookModel::instance()->with(['author','comments'])->select();

    // 直接传入关联模型配置，将与预定义关联模型合并
    BookModel::with(['author'=>[
        'relation_model' => AuthorModel::class,
        'relation_table' => 'authors',
        'relation_key'   => 'id',
        'foreign_key'    => 'author_id',
        'mapping_name'   => 'author',
        'mapping_type'   => RelationModel::BELONGS_TO,
        'condition'      => [],
    ]])->select();

    // 设置关联查询回调
    BookModel::with(['author'=>function($query){
        $query->where(['name'=>'张三']);
    }])->select();

    // 子关联模型
    BookModel::with(['author.books','comments'=>['with'=>'user']])->select();
    BookModel::with(['author.books','comments'=>['with'=>['user']]])->select();

    // 子关联模型动态配置
    BookModel::with(['author.books' => [
        'condition' => false, // 取消默认查询条件
    ],'comments'])->select();

    // 子关联模型查询回调
    BookModel::with(['author.books' => function($query){
        $query->where(['sale_num', ['gt', 100]]);
    },'comments'])->select();
    ```

## 建议

- 不建议使用D方法来实例化模型，对代码提示不友好
- 尽量为每个表都创建模型
