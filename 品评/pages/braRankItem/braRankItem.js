// pages/braRankItem/braRankItem.js

// 获取API
var URL = require('../../utils/config.default.js');
// 获取网络请求
var Req = require("../../utils/request.js");
// 获取小程序实例
var app = getApp();

Page({

  /**
   * 页面的初始数据
   */
  data: {
    // 存储小程序码地址
    codeUrl: '',
    itemid: '',
    list: [],
    title: '',
    // 手机型号等信息
    pixelRatio: '',
    screenHeight: '',
    screenWidth: '',
    windowHeight: '',
    windowWidth: '',
    // 用户信息
    nickName: '',
    avatarUrl: '',
    localAvatarUrl: '',
    actionSheetHidden: true
  },
  /**
   * 点击报名按钮
   */
  showtips: function () {
    wx.showModal({
      title: '去浏览器报名吧',
      content: 'www.10pinping.com',
      confirmText: '复制网址',
      confirmColor: '#2061b1',
      success: function (res) {
        if (res.confirm) {
          wx.setClipboardData({
            data: 'www.10pinping.com',
            success: function (res) {
              wx.showToast({
                title: '复制成功',
                icon: 'none',
                duration: 2000
              })
            }
          })
        } else if (res.cancel) {
          wx.showToast({
            title: '复制失败',
            icon: 'none',
            duration: 2000
          })
        }
      },
      fail: function (res) { },
      complete: function (res) { },
    })
  },
  /**
   * 显示底部菜单
   */
  showActionSheet: function () {
    var that = this;
    that.setData({
      actionSheetHidden: false
    });
  },
  /**
   * 隐藏底部菜单
   */
  hideActionSheet: function () {
    var that = this;
    that.setData({
      actionSheetHidden: true
    });
  },
  /**
   * 显示小程序码
   */
  showCode: function () {
    var that = this;
    // 预览小程序码
    wx.previewImage({
      urls: [that.data.codeUrl]
    });
  },
  /**
   * 获取小程序码
   */
  createCode: function () {
    var that = this;
    Req.POST(URL.RGETCODE, {
      params: {
        file_name: 'b' + app.globalData.itemid + '.png',
        itemid: app.globalData.itemid
      },
      success: function (res) {
        // 下载小程序码图片到本地（临时）
        wx.downloadFile({
          url: res.data.tuurl,
          success: function (res) {
            that.setData({
              codeUrl: res.tempFilePath
            });
          }
        });
      },
      fail: function (res) { },
      complete: function (res) { }
    });
  },
  /**
   * 生成朋友圈分享图
   */
  createShareImg: function () {
    wx.showToast({
      title: '图片吐血生成中',
      icon: 'loading',
      duration: 3000
    });
    var that = this;
    var h = that.data.windowHeight
    var w = that.data.windowWidth;
    var p = that.data.pixelRatio;
    const ctx = wx.createCanvasContext('shareCanvas');
    // 绘制背景图
    ctx.drawImage('../../images/share_img_bg2.png', 0, 0, w, h);
    // 绘制用户信息
    ctx.save();
    ctx.arc(110 * h / 1334, 90 * h / 1334, 60 * h / 1334, 0, 2 * Math.PI);
    ctx.clip();
    var wxcode = that.data.codeUrl;
    var avatarUrl = that.data.avatarUrl;
    ctx.drawImage(avatarUrl, 50 * h / 1334, 30 * h / 1334, 120 * h / 1334, 120 * h / 1334);
    ctx.restore();
    ctx.save();
    ctx.setFillStyle('#fff');
    ctx.setFontSize(27 * h / 1334);
    ctx.fillText(that.data.nickName, 210 * h / 1334, 84 * h / 1334);
    ctx.fillText("分享了" + that.data.title, 210 * h / 1334, 124 * h / 1334);
    ctx.save();
    ctx.setFillStyle('#fff');
    ctx.setFontSize(60 * h / 1334);
    ctx.fillText("品牌排行榜", 270 * h / 1334, 380 * h / 1334);
    ctx.restore();
    ctx.setFillStyle('#fff');
    ctx.setFontSize(48 * h / 1334);
    ctx.fillText("长按识别小程序", 400 * h / 1334, 1130 * h / 1334);
    ctx.fillText("立即参与！", 400 * h / 1334, 1220 * h / 1334);
    ctx.restore();
    // 绘制小程序码
    ctx.save();
    ctx.beginPath();
    ctx.setStrokeStyle('#00834a')
    ctx.arc(200 * h / 1334, 1162 * h / 1334, 130 * h / 1334, 0, 2 * Math.PI);
    ctx.clip();
    ctx.stroke();
    ctx.drawImage(wxcode, 70 * h / 1334, 1032 * h / 1334, 260 * h / 1334, 260 * h / 1334);
    ctx.closePath();
    ctx.draw(false, function (res) {
      // 把当前画布指定区域的内容导出生成指定大小的图片，并返回文件路径
      wx.canvasToTempFilePath({
        canvasId: 'shareCanvas',
        success: function (res) {
          
          if (res.tempFilePath) {
            wx.hideToast();
            wx.previewImage({
              urls: [res.tempFilePath],
              success: function (res) {}
            });
          }
        }
      }, this)
    }
    );
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this;
    wx.getSystemInfo({
      success: function (res) {
        that.setData({
          pixelRatio: res.pixelRatio,
          windowWidth: res.windowWidth,
          windowHeight: res.windowHeight,
          screenHeight: res.screenHeight,
          screenWidth: res.screenWidth
        })
      },
    });
    // 页面加载时就生成小程序码
    that.createCode();
    wx.getUserInfo({
      withCredentials: false,
      success: function (res) {
        var avatarUrl, nickName;
        avatarUrl = res.userInfo.avatarUrl;
        nickName = res.userInfo.nickName;
        that.setData({
          nickName: nickName
        });
        wx.downloadFile({
          url: avatarUrl,
          success: function (res) {
            that.setData({
              avatarUrl: res.tempFilePath
            });
          }
        });
      }
    });
    var itemid = app.globalData.itemid;
    var title = app.globalData.title;
    that.setData({
      itemid: itemid,
      title: title
    });
    var params = {
      action: 'getlist',
      itemid: itemid
    };
    Req.POST(URL.BRARANK_DETAIL, {
      params: params,
      success: function(res) {
        // 设置导航标题
        wx.setNavigationBarTitle({
          title: res.data.title
        });
        if(res.data.status == 1){
          that.setData({
            list: res.data.list,
            title: res.data.title
          });
        }
      },
      fail: function (res) { },
      complete: function (res) {}
    });
    wx.showShareMenu({
      withShareTicket: true
    });
  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
    var that = this;
    that.setData({
      list: []
    });
    that.onLoad();
    wx.stopPullDownRefresh();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    var that = this;
    var itemid = that.data.itemid;
    var title = that.data.title;
    return {
      title: title,
      path: '/pages/find/find?itemid=' + itemid + '&pagename=brarank',
      imageUrl: '../../images/share_braRank_img.png',
      success: function (res) {
        var shareTickets = res.shareTickets;
        if (shareTickets.length == 0) {
          return false
        }
        wx.getShareInfo({
          shareTicket: shareTickets[0],
          success: function (res) {
            var encryptedData = res.encryptedData;
            var iv = res.iv;
          }
        });
      },
      fail: function (res) {
        // do fail
      }
    }
  }
})