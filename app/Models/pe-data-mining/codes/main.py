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

def get_data(one_hot=False):
    category_columns = ["性别", "是否吸烟", "早发心血管病家族史", "亚硝酸盐", "尿糖", "尿胆原", "白细胞", "胆红素", "蛋白质", "透明度", "酮体", "隐血或红细胞", "颜色"]

    data = Data("D:\\Lab\\PE\\data\\csv", "D:\\Lab\\PE\\data\\processed")
    all1, all2 = data.load_X()
    # label = data.gen_label(all1, all2, "低密度脂蛋白", 2.1, better_ratio=0.05, label="Y")
    # label = data.gen_label(all1, all2, "尿酸", 420, label="Y")
    label = data.gen_label(all1, all2, "甘油三酯", 1.7, label="Y", better_ratio=0.15)
    # label = data.gen_label(all1, all2, "同型半胱氨酸", 1.7, label="Y")

    all1 = all1.dropna(axis=0, how="any")
    section_category = all1[category_columns]
    section_digit = all1[list(set(all1.keys()).difference(set(category_columns)))]
    section_category = section_category.apply(LabelEncoder().fit_transform, axis=0)

    def convert_digit(a):
        if isinstance(a,str):
            if a[0] == "<" or a[0] == ">":
                a = float(a[1:])
        return a

    section_digit = section_digit.applymap(lambda x: convert_digit(x))
    all1 = section_digit.join(section_category)
    all1 = all1[sorted(all1.columns.tolist())]

    if one_hot:
        all = all1.join(label).reset_index()
        section_category = all[category_columns]
        section_digit = all[list(set(all.keys()).difference(set(category_columns)).difference(set(["Y", "体检号"])))]
        section_category = OneHotEncoder().fit_transform(section_category.values)
        section_digit = section_digit.apply(lambda x: (x.astype("float32") - np.min(x.astype("float32"))) / (np.max(x.astype("float32")) - np.min(x.astype("float32"))))
        section_category = np.array(section_category.toarray())
        section_digit = np.array(section_digit.values)
        print(section_category.shape)
        print(section_digit.shape)
        X = np.hstack((section_category, section_digit))
        y = all["Y"].values.flatten()
        # cate_y = []
        # for v in y:
        #     cate_y.append([0, 1] if v else [1, 0])
        # y = np.array(cate_y)
        print(X.shape)

    else:
        X = all1.values.astype("float32")
        y = all1.join(label)["Y"].values.flatten()
        print(np.size(y), "   ", np.size(y[y==1])/(float)(np.size(y)))

    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.33, random_state=42)
    return X_train, X_test, y_train, y_test, all1.keys().tolist()


def decisionTree():
    X_train, X_test, y_train, y_test, X_labels = get_data(one_hot=False)
    clf = tree.DecisionTreeClassifier()
    clf.fit(X_train, y_train)
    y_pred = clf.predict(X_test)
    print(metrics.classification_report(y_test, y_pred))
    # for i, v in enumerate(clf.feature_importances_):
    #     print(X_labels[i], ",", v)


def xgboost():
    X_train, X_test, y_train, y_test, X_labels = get_data(one_hot=False)
    xr = xgb.XGBClassifier(learning_rate=0.001, n_estimators=1000, n_jobs=8, min_child_weight=2, max_depth=7)
    xr.fit(X_train, y_train)
    y_pred = xr.predict(X_test)
    print(metrics.classification_report(y_test, y_pred))


def randomForest():
    X_train, X_test, y_train, y_test, X_labels = get_data(one_hot=False)
    rf = RandomForestClassifier(n_estimators=100)
    rf.fit(X_train, y_train)
    y_pred = rf.predict(X_test)
    print(metrics.classification_report(y_test, y_pred))

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
    模型需要知道输入数据的shape，
    因此，Sequential的第一层需要接受一个关于输入数据shape的参数，
    后面的各个层则可以自动推导出中间数据的shape，
    因此不需要为每个层都指定这个参数
    '''

    # 输入层有784个神经元
    # 第一个隐层有512个神经元，激活函数为ReLu，Dropout比例为0.2
    model.add(Dense(64, input_shape=(X_train.shape[1],)))
    model.add(Activation('relu'))
    model.add(Dropout(0.2))

    # 第二个隐层有512个神经元，激活函数为ReLu，Dropout比例为0.2
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

    model_checkpoint = ModelCheckpoint('./models/weights.{epoch:04d}-{val_loss:.4f}.hdf5', monitor='val_loss',
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

mlp()
decisionTree()
xgboost()
randomForest()

# gridSearchCV()


