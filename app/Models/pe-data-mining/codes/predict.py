from data import *
import pandas as pd
import numpy as np
from sklearn import tree
from sklearn import metrics
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, OneHotEncoder
import xgboost as xgb
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import GridSearchCV
from sklearn.externals import joblib
import json
from collections import defaultdict
import sys, io

base_path = "/Users/netlab/Server/medical-platform/app/Models/pe-data-mining/codes/"
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

def get_data(input, one_hot=False):
    category_columns = ["性别", "是否吸烟", "早发心血管病家族史", "亚硝酸盐", "尿糖", "尿胆原", "白细胞", "胆红素", "蛋白质", "透明度", "酮体", "隐血或红细胞", "颜色"]

    data = Data("/Users/netlab/Server/medical-platform/app/Models/pe-data-mining/data/csv", "/Users/netlab/Server/medical-platform/app/Models/pe-data-mining/data/processed")
    all1, all2 = data.load_X()


    all1 = all1.dropna(axis=0, how="any")
    section_category = all1[category_columns]
    encoders = defaultdict(LabelEncoder)
    section_category.apply(lambda x: encoders[x.name].fit(x), axis=0)

    def convert_digit(a):
        if isinstance(a,str):
            if a[0] == "<" or a[0] == ">":
                a = float(a[1:])
        return a


    section_category = input[category_columns]
    section_digit = input[list(set(input.keys()).difference(set(category_columns)))]
    section_category = section_category.apply(lambda x: encoders[x.name].transform(x), axis=0)
    section_digit = section_digit.applymap(lambda x: convert_digit(x))
    X = section_digit.join(section_category)
    X = X[sorted(X.columns.tolist())]
    X = X.values.astype("float32")

    return X


def decisionTree(input):
    ret = {}
    X = get_data(input, one_hot=False)
    clf = joblib.load(base_path + "models/NS.decisionTree.model")
    ret["NS"] = clf.predict(X).tolist()
    clf = joblib.load(base_path + "models/LDLC.decisionTree.model")
    ret["LDLC"] = clf.predict(X).tolist()
    clf = joblib.load(base_path + "models/GYSZ.decisionTree.model")
    ret["GYSZ"] = clf.predict(X).tolist()
    return ret

def xgboost(input):
    ret = {}
    X = get_data(input, one_hot=False)
    clf = joblib.load(base_path + "models/NS.xgboost.model")
    ret["NS"] = clf.predict(X).tolist()
    clf = joblib.load(base_path + "models/LDLC.xgboost.model")
    ret["LDLC"] = clf.predict(X).tolist()
    clf = joblib.load(base_path + "models/GYSZ.xgboost.model")
    ret["GYSZ"] = clf.predict(X).tolist()
    return ret


def randomForest(input):
    ret = {}
    X = get_data(input, one_hot=False)
    clf = joblib.load(base_path + "models/NS.randomForest.model")
    ret["NS"] = clf.predict(X).tolist()
    clf = joblib.load(base_path + "models/LDLC.randomForest.model")
    ret["LDLC"] = clf.predict(X).tolist()
    clf = joblib.load(base_path + "models/GYSZ.randomForest.model")
    ret["GYSZ"] = clf.predict(X).tolist()
    return ret

def mlp(load_from_save=False, run=True):
    from keras.models import Sequential
    from keras.layers.core import Dense, Dropout, Activation
    from keras.optimizers import RMSprop
    from keras.callbacks import ModelCheckpoint
    X_train, X_test, y_train, y_test, X_labels = get_data(one_hot=True)
    print(X_train)
    batch_size = 16
    nb_epoch = 500
    model = Sequential()
    '''
    ģ����Ҫ֪���������ݵ�shape��
    ��ˣ�Sequential�ĵ�һ����Ҫ����һ��������������shape�Ĳ�����
    ����ĸ�����������Զ��Ƶ����м����ݵ�shape��
    ��˲���ҪΪÿ���㶼ָ���������
    '''

    # �������784����Ԫ
    # ��һ��������512����Ԫ�������ΪReLu��Dropout����Ϊ0.2
    model.add(Dense(64, input_shape=(X_train.shape[1],)))
    model.add(Activation('relu'))
    model.add(Dropout(0.2))

    # �ڶ���������512����Ԫ�������ΪReLu��Dropout����Ϊ0.2
    model.add(Dense(64))
    model.add(Activation('relu'))
    model.add(Dropout(0.2))

    model.add(Dense(64))
    model.add(Activation('relu'))
    model.add(Dropout(0.2))

    model.add(Dense(1))
    model.add(Activation('sigmoid'))

    model.summary()

    model.compile(loss='binary_crossentropy',
                  optimizer=RMSprop(lr=0.0001),
                  metrics=['accuracy'])

    model_checkpoint = ModelCheckpoint(base_path + 'models/weights.{epoch:04d}-{val_loss:.4f}.hdf5', monitor='val_loss',
                                       save_best_only=False)
    history = model.fit(X_train, y_train,
                        batch_size=batch_size,
                        nb_epoch=nb_epoch,
                        verbose=1,
                        validation_data=(X_test, y_test),
                        callbacks=[model_checkpoint]
                        )
    y_pred = model.predict(X_test)
    # y_pred = np.argmax(y_pred, axis=1).astype(bool)
    # y_test2 = np.argmax(y_test, axis=1).astype(bool)
    p = np.zeros(y_pred.shape)
    p[y_pred > 0.5] = 1
    print(metrics.classification_report(y_test, p))

def gridSearchCV():
    X_train, X_test, y_train, y_test, X_labels = get_data(one_hot=False)
    X = np.vstack((X_train, X_test))
    y = np.append(y_train, y_test)
    print(X.shape, y.shape)
    params = {
        'max_depth': range(3, 10, 1),
        'min_child_weight': range(1, 6, 1)
    }

    clf = xgb.XGBClassifier(learning_rate=0.001, n_estimators=1000, n_jobs=8)
    gscv = GridSearchCV(estimator=clf, param_grid=params, cv=5, scoring="f1",verbose=1)
    gscv.fit(X_train,y_train)
    print(gscv.best_params_, gscv.best_score_)
    y_pred = gscv.predict(X_test)
    print(metrics.classification_report(y_test, y_pred))


f = open(sys.argv[1], "r")
input_str = f.read()
f.close()
input = json.loads(input_str)
input = pd.DataFrame.from_dict([input])
input = Data("", "").data_fix(input)

# mlp()
ret = decisionTree(input)
# xgboost(input)
# randomForest(input)

print(json.dumps(ret))


# gridSearchCV()


