# API文档

* 服务器base url为http://app.pku.edu.cn:8103/api
* 传参：如非特殊说明,全部采用x-www-form-urlencoded传参
* 返回值形式：
    * 成功：{"success": true,"data": ""  //此处格式根据接口不同而不同}
    * 失败：{"success": false,"data": "失败原因"}
* 认证：使用cookie，请保留cookie


## 注册
* URL: /user/register
* 方法: POST
* 参数: 
| 名称 | 必填 | 说明 |
| email | true | |
| name | true | |
| password | true | |
* 返回值:
```$json
{
    "id": 1,
    "name": "zakelly",
    "email": "lzq82555906@sina.com",
    "email_verified_at": null,
    "created_at": "2019-01-17 06:53:07",
    "updated_at": "2019-01-17 06:53:07"
}
```

## 登录
* URL: /user/login
* 方法: POST
* 参数: 

| 名称 | 必填 | 说明 |
| -- | -- | -- |
| email | true | |
| password | true | |

* 返回值:
```$json
{
    "id": 1,
    "name": "zakelly",
    "email": "lzq82555906@sina.com",
    "email_verified_at": null,
    "created_at": "2019-01-17 06:53:07",
    "updated_at": "2019-01-17 06:53:07"
}
```


## 登出
* URL: /user/logout
* 方法: POST
* 参数: 无
* 返回值: 无


## 获得当前登录用户
* URL: /user
* 方法: GET
* 参数: 无
* 返回值:
```$json
{
    "id": 1,
    "name": "zakelly",
    "email": "lzq82555906@sina.com",
    "email_verified_at": null,
    "created_at": "2019-01-17 06:53:07",
    "updated_at": "2019-01-17 06:53:07"
}
```


## 获得实体元信息
* URL: /type
* 方法: GET
* 参数: 无
* 返回值: 数组
```$json
 [
    {
        "id": 1,  // id 之后接口会用到
        "name": "image",   // 名称
        "type": "object",  // 类型，目前有object /record / timeseries 三种
        "created_at": null,
        "updated_at": null
    },
    {
        "id": 2,
        "name": "record",
        "type": "record",
        "created_at": null,
        "updated_at": null
    },
    {
        "id": 3,
        "name": "ecg",
        "type": "timeseries",
        "created_at": null,
        "updated_at": null
    }
]

```



## 上传object类型实体
* URL: /type/{type_id}
* 方法: POST
* 参数: multipart/form-data上传文件，参数名为"file"
* 返回值: 
```$json
{
     "name": "object/hZetNHcRLldPsSLHzLcobQyFVyAGnp9p3sM0cRtz.png",   
     "user_id": 2,
     "meta_id": 1,
     "time": "2019-02-17 08:00:40",
     "updated_at": "2019-02-17 08:00:40",
     "created_at": "2019-02-17 08:00:40",
     "id": 6 //取回时需要用到
}

```


## 上传timeseries类型实体
* URL: /type/{type_id}
* 方法: POST
* 参数: raw方式传递json数组，每个json中需要有value, sec, usec 三个字段，分别代表值、unix时间戳、微秒数
* 返回值: 无


## 上传record类型实体
* URL: /type/{type_id}
* 方法: POST
* 参数: raw方式传递json数组
* 返回值: 无
* 注意：应当避免key开头是下划线_或者key为id的情况出现


## 获得object类型实体列表
* URL: /type/{type_id}
* 方法: GET
* 参数: 

| 名称 | 必填 | 说明 |
| tmin | false | 最小时间戳（包括），默认0 |
| tmax | false | 最大时间戳（不包括），默认当前时间 |
| offset | false | 跳过多少个内容，默认0 |
| limit | false | 返回值限制个数，默认20 |

* 说明：返回值按时间倒序返回
* 返回值: 数组
```$json
[
    {
        "id": 3,
        "user_id": 1,
        "meta_id": 1,
        "name": "object/jePv1mZoGmg8LH5rOExvWqWcOHwdopiPxcqjHii7.png",
        "time": "2019-01-17 08:17:15",
        "created_at": "2019-01-17 08:17:15",
        "updated_at": "2019-01-17 08:17:15"
    }
]
```


## 获得timeseries类型实体列表
* URL: /type/{type_id}
* 方法: GET
* 参数: 

| 名称 | 必填 | 说明 |
| tmin | false | 最小时间戳（包括），默认0 |
| tmax | false | 最大时间戳（不包括），默认当前时间 |

* 说明：返回值按时间倒序返回
* 返回值: 数组
```$json
[
    {
        "time": "2019-02-17T06:58:29.000929Z",
        "tag": "sdf2222we"
    },
    {
        "time": "2019-02-17T06:58:23.000929Z",
        "tag": "sdfwe"
    }
]
```


## 获得record类型实体列表
* URL: /type/{type_id}
* 方法: GET
* 参数: 

| 名称 | 必填 | 说明 |
| tmin | false | 最小时间戳（包括），默认0 |
| tmax | false | 最大时间戳（不包括），默认当前时间 |
| offset | false | 跳过多少个内容，默认0 |
| limit | false | 返回值限制个数，默认20 |

* 说明：返回值按时间倒序返回
* 返回值: 数组
```$json
[
    {
        "value": 15,  //上传的所有的域都有
        "value2": "16",
        "value3": false,
        "_time": 1550472072,
        "id": "5c6a53884908a128c03e4cf3"
    }
]
```

## 获得object类型实体
* URL: /type/{type_id}/{id}
* 方法: GET
* 参数: 无
* 返回值: 文件下载

## 获得timeseries类型实体
* URL: /type/{type_id}/{tag}
* 方法: GET
* 参数: 

| 名称 | 必填 | 说明 |
| tmin | false | 最小时间戳（包括），默认0 |
| tmax | false | 最大时间戳（不包括），默认当前时间 |
| offset | false | 跳过多少个内容，默认0 |
| limit | false | 返回值限制个数，默认20 |

* 说明：返回值按时间倒序返回
* 返回值: 数组
```$json
[
    {
        "time": "2019-02-17T06:58:23.000929Z",
        "value": 15
    }
]
```

## 获得record类型实体
* URL: /type/{type_id}/{id}
* 方法: GET
* 参数: 无
* 返回值:
```$json
{
    "value": 15,
    "sec": 1550386703,
    "usec": 929,
    "_time": 1550472072,
    "id": "5c6a53884908a128c03e4cf3"
}
```






