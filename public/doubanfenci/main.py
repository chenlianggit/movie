#!/usr/bin/env python 
# -- coding: utf-8 -- 
# @Time     : 2018/11/23 上午11:23
# @Author   : 五年陈
# @File     : main.py

import random

import flask
import requests
from PIL import Image
from flask import request
from flask import jsonify

import warnings
warnings.filterwarnings("ignore")
import jieba    #分词包
import numpy    #numpy计算包
import re
import pandas as pd
from urllib import parse
import matplotlib
matplotlib.rcParams['figure.figsize'] = (10.0, 5.0)
from wordcloud import WordCloud #词云包
import numpy as np



ip_proxy = [
    '183.147.209.74:4242',

]
def get_random_ip():
    proxy_host = 'http://{}'.format(random.choice(ip_proxy))
    proxies = {"https": proxy_host}
    return proxies

def getMovieIdHeader():
    headers = {
        "Accept": "*/*",
        "Accept-Encoding": "gzip, deflate, br",
        "Accept-Language": "zh-CN,zh;q=0.8",
        "Cache-Control": "no-cache",
        "Connection": "keep-alive",
        "content-type": "application/json",
        "Host": "m.douban.com",
        "Pragma": "no-cache",
        "Referer": "https://servicewechat.com/wx2f9b06c1de1ccfca/devtools/page-frame.html",
        "User-Agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1 wechatdevtools/1.02.1810250 MicroMessenger/6.5.7 Language/zh_CN webview/",
    }
    return headers

def getMovieCommentHeader():
    headers = {
        "Accept": "*/*",
        "Accept-Encoding": "gzip, deflate, br",
        "Accept-Language": "zh-CN,zh;q=0.8",
        "Cache-Control": "no-cache",
        "Connection": "keep-alive",
        "content-type": "application/x-www-form-urlencoded",
        "Host": "frodo.douban.com",
        "Pragma": "no-cache",
        "Referer": "https://servicewechat.com/wx2f9b06c1de1ccfca/devtools/page-frame.html",
        "User-Agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1 wechatdevtools/1.02.1810250 MicroMessenger/6.5.7 Language/zh_CN webview/",
        "X-Api-Key": "054022eaeae0b00e0fc068c0c0a2102a",
        "X-Appid": "wx2f9b06c1de1ccfca"
    }
    return headers



def get(url, headers):
    # data = requests.get(url, headers=headers,proxies=get_random_ip(), verify=False)
    data = requests.get(url, headers=headers)
    print(data)
    return data.json()

#获取豆瓣视频ID
def getMovieId(name):
    name = parse.quote(name)
    url = "https://m.douban.com/rexxar/api/v2/search?q={}&type=movie&app_version=5.0.0".format(name)

    data = get(url, getMovieIdHeader())
    id = int(data['subjects'][0]['id'])
    print("豆瓣视频ID:",id)
    if id <= 0 :
        return False
    return id


#获取评论
def getCommentsById(movieId, count=10):
    eachCommentList = []
    YingPingUrl = "https://frodo.douban.com/api/v2/movie/{}/reviews?count={}&app_version=5.0.0&apikey=054022eaeae0b00e0fc068c0c0a2102a&appid=wx2f9b06c1de1ccfca".format(str(movieId), str(count))
    PingLun     = "https://frodo.douban.com/api/v2/movie/{}/interests?count={}&following=1&app_version=5.0.0&apikey=054022eaeae0b00e0fc068c0c0a2102a&appid=wx2f9b06c1de1ccfca".format(str(movieId), str(count))
    data = get(YingPingUrl, getMovieCommentHeader())
    data_ping = get(PingLun, getMovieCommentHeader())

    # 普通评论
    list_ping = data_ping['interests']
    for item in list_ping:
        eachCommentList.append(item['comment'])
    # 专业影评
    list = data['reviews']
    for item in list:
        eachCommentList.append(item['abstract'])
    return eachCommentList

server = flask.Flask(__name__)
@server.route('/main', methods=['get', 'post'])
def main():
    name = request.values.get('name','') # %E5%93%88%E5%93%88
    if name == "":
        return ''
    name = parse.unquote(name) # 解码字符串
    print(name)
    try:
        id = getMovieId(name)
    except:
        id = 0

    commentList = []
    if int(id) > 0:
        try:
            # 循环获取第一个电影的前10页评论
            commentList = getCommentsById(id, 10000)
        except:
            pass
    commentList.append(name)

    #将列表中的数据转换为字符串
    comments = ''
    for k in range(len(commentList)):
        comments = comments + (str(commentList[k])).strip()


    #使用正则表达式去除标点符号
    pattern = re.compile(r'[\u4e00-\u9fa5]+')
    filterdata = re.findall(pattern, comments)
    cleaned_comments = ''.join(filterdata)


    #使用结巴分词进行中文分词
    segment = jieba.lcut(cleaned_comments)
    words_df=pd.DataFrame({'segment':segment})

    #去掉停用词
    stopwords=pd.read_csv("stopwords.txt",index_col=False,quoting=3,sep="\t",names=['stopword'], encoding='utf-8')#quoting=3全不引用
    words_df=words_df[~words_df.segment.isin(stopwords.stopword)]

    #统计词频
    words_stat=words_df.groupby(by=['segment'])['segment'].agg({"计数":numpy.size})
    words_stat=words_stat.reset_index().sort_values(by=["计数"],ascending=False)

    img = Image.open('wb.png')  # 打开图片
    img_array = np.array(img)  # 将图片装换为数组

    #用词云进行显示
    wordcloud=WordCloud(
        font_path="simhei.ttf",
        width=750,
        height=1334,
        # background_color="white",
        background_color="black",
        max_font_size=135,
        max_words=200,
        mask=img_array,

    )
    word_frequence = {x[0]:x[1] for x in words_stat.head(1000).values}

    word_frequence_list = []
    for key in word_frequence:
        temp = (key,word_frequence[key])
        word_frequence_list.append(temp)
    word_frequence_list = dict(word_frequence_list)
    wordcloud=wordcloud.fit_words(word_frequence_list)


    wordcloud.to_file('img/{}.png'.format(str(id)))
    hostUrl = 'http://douban.q2017.com/'
    # data = {
    #     'img':'{}.png'.format(str(id))
    # }
    return hostUrl + '{}.png'.format(str(id))


if __name__ == '__main__':
    #指定端口、host,0.0.0.0代表不管几个网卡，任何ip都可以访问
    server.run(debug=True, port=6789, host='0.0.0.0')