# ThinkPHP

ThinkPHP3.2.3 修改版

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
