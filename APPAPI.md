# 应用系统API文档

* 服务器base url为http://app.pku.edu.cn:8103/app
* 传参：如非特殊说明,全部采用x-www-form-urlencoded传参
* 返回值形式：
    * 成功：{"success": true,"data": ""  //此处格式根据接口不同而不同}
    * 失败：{"success": false,"data": "失败原因"}
* 认证：使用cookie，请保留cookie，和数据平台系统共用一套


## 开始ecg预测
* URL: /ecg/predict
* 方法: POST
* 参数: 

| 名称 | 必填 | 说明 |
| tag | true | ecg的tag |

* 返回值: 无



## 获得ecg预测
* URL: /ecg/predict
* 方法: GET
* 参数: 

| 名称 | 必填 | 说明 |
| tag | true | ecg的tag，上传时的那个，代表预测这一段 |

* 返回值: 一个字符串代表结果。


## 获得ecg统计信息
* URL: /ecg/history
* 方法: GET
* 参数: 

| 名称 | 必填 | 说明 |
| tmax | false | 最大时间戳（包括），默认当前时间 |
| tmin | false | 最小时间戳（包括），默认tmax - 15天 |

*返回值：每天的使用情况，一天一个结构，组成一个数组。

```json
{
    "id": 1,
    "user_id": 3,
    "date": "2019-04-01",   //哪天
    "count": 2,           //测量次数
    "duration": 3,        //测量持续的秒数
    "created_at": "2019-04-02 10:21:50",
    "updated_at": "2019-04-02 10:21:50"
},
{
    "id": 2,
    "user_id": 3,
    "date": "2019-04-02",
    "count": 2,
    "duration": 3,
    "created_at": "2019-04-02 10:27:16",
    "updated_at": "2019-04-02 10:27:16"
},
{
    "id": 3,
    "user_id": 3,
    "date": "2019-04-03",
    "count": 2,
    "duration": 3,
    "created_at": "2019-04-02 18:30:18",
    "updated_at": "2019-04-02 18:30:18"
}
```


## 开始体检预测
* URL: /pe/predict
* 方法: POST
* 参数: 

| 名称 | 必填 | 说明 |
| id | true | 体检记录的id |

* 返回值: 无



## 获得体检预测
* URL: /ecg/predict
* 方法: GET
* 参数: 

| 名称 | 必填 | 说明 |
| tag | true | 体检记录的id，上传时的那个，代表预测这一段 |

* 返回值: 一个字符串代表结果。"{\"LDLC\": [1], \"GYSZ\": [1], \"NS\": [0]}", 分别为三种指标是否会有问题


## 发信息
* URL: /messages
* 方法: POST
* 参数: 

| 名称 | 必填 | 说明 |
| to | true | 对话的user_id |
| content | true | 内容 |

* 返回值: Message结构
```json
{
    "id": 5,
    "from_user_id": 3,
    "to_user_id": 1,
    "type": 0,
    "content": "123123123213",
    "created_at": "2019-04-07 20:04:43",
    "updated_at": "2019-04-07 20:04:43"
}
```


## 获得消息列表联系人
* URL: /contacts
* 方法: GET
* 参数: 

| 名称 | 必填 | 说明 |
| offset | false | 跳过多少个内容，默认0 |
| limit | false | 返回值限制个数，默认20 |

* 返回值: 
```json
[
    {
        "to_user_id": 1,
        "unread": 0,
        "last": "123123123213",
        "last_message_time": "2019-04-07 20:14:18",
        "created_at": "2019-04-07 20:04:12",
        "updated_at": "2019-04-07 20:14:18",
        "to_user": {
            "id": 1,
            "name": "zakelly",
            "email": "lzq82555906@sina.com",
            "email_verified_at": null,
            "created_at": "2019-01-17 06:53:07",
            "updated_at": "2019-01-17 06:53:07",
            "avatar": "https://ss1.bdstatic.com/70cFvXSh_Q1YnxGkpoWK1HF6hhy/it/u=3200113721,4238345508&fm=27&gp=0.jpg",
            "nickname": "Zakelly"
        }
    }
]
```


## 获得消息
* URL: /messages
* 方法: GET
* 参数: 

| 名称 | 必填 | 说明 |
| to | true | 对话的user_id |
| offset | false | 跳过多少个内容，默认0 |
| limit | false | 返回值限制个数，默认20 |

* 返回值:   Message结构列表
```json
[
    {
        "id": 5,
        "from_user_id": 3,
        "to_user_id": 1,
        "type": 0,
        "content": "123123123213",
        "created_at": "2019-04-07 20:04:43",
        "updated_at": "2019-04-07 20:04:43"
    }
]
```

## 获得设备列表
* URL: /devices
* 方法: GET
* 参数: 无

* 返回值:   Device结构列表
```json
[        
    {
        "token": "sdfwefwef",   //设备唯一标识符
        "device_info": "iphone 6s",   //设备描述
        "created_at": "2019-04-11 11:33:43",
        "updated_at": "2019-04-11 11:33:48"
    }
]
```



## 注册设备
* URL: /devices
* 方法: POST
* 参数: 

| 名称 | 必填 | 说明 |
| token | true | 设备唯一标识符 |
| device_info | true | 设备描述 |

* 返回值: Device结构
```json
{
    "token": "sdfwefwef",   //设备唯一标识符
    "device_info": "iphone 6s",   //设备描述
    "created_at": "2019-04-11 11:33:43",
    "updated_at": "2019-04-11 11:33:48"
}
```
