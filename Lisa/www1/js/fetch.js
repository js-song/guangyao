var config = new Config();


$(function() {
	$.ajax({
		type: config.mainPage.type,
		url: config.mainPage.url,
		data: {
			action:"main"
		},
		async:true,
		dataType:"json",
		success: function(res) {
			if(res.status != 1) {return};
			
			var data = res.data;
			var bus = data.bus_data;
			var con = data.con_data;
			var news = data.news_data;
			
			
			
			//轮播图
			var banner = data.logo.slideshow;
			
			var bannerimg="";
			for(var i = 0;i<banner.length;i++){
				bannerimg+="<div class='swiper-slide'><img src='"+banner[i]+"' alt=''></div>";
			}
			$(".swiper-wrappero").html(bannerimg);
			
			//手机轮播
			$(".swiper-wrappers").html(bannerimg);
			
			//业务
			var ywhtm = "";
			for (var i = 0; i < bus.length; i++) {
				ywhtm+= "<li class='col-md-4 col-sm-4 col-lg-4 col-xs-6'>"+
							"<a href='javascript:;' target=''>"+
								"<dl>"+
									"<dt><img src='"+bus[i].bus_imgurl+"' alt='' /></dt>"+
									"<dd><h2>"+bus[i].title+"</h2></dd>"+
									"<dd><p>"+bus[i].explains+"</p></dd>"+
								"</dl>"+
							"</a>"+
						"</li>";
			}
			ywhtm+="<div class='clear'></div>";
			$(".ywlist").html(ywhtm);
			
			//新闻
			var newonehtm=""
			var newsone = data.news_datas;
//			console.log(newsone);
			
			newonehtm= "<a href='newsxq.html?a="+newsone.newsid+ " ' target=''>"+
							"<dl>"+
								"<dt><img src='"+newsone.imgurl+ "' alt='' /></dt>"+
								"<dd><h3>"+newsone.articletitle+"</dd>"+
								"<dd><p>"+newsone.editorValue+"</p></dd>"+
							"</dl>"+
						"</a>"
//			console.log(newonehtm);
			$(".newspic").html(newonehtm);
			
			var newshtm = "";
			var newdata = res.data.news_data;
			
			for (var i=0;i<news.length;i++){
				newshtm+="<li>"+
							"<time>"+
								"<span class='day'>"+news[i].date+"</span>"+
								"<span class='rq'>"+news[i].addtime+"</span>"+
							"</time>"+
							"<a href='newsxq.html?a="+newdata[i].newsid+ " ' target=''>"+
								"<div class='newlistcon'>"+
									"<h3>"+news[i].articletitle+"</h3>"+
									"<p>"+news[i].editorValue+"</p>"+
								"</div>"+
							"</a>"+
						"</li>"
			}
				newshtm+="<div class='clear''></div>";
				//console.log(ywhtm);
				$(".newslist").html(newshtm);
				
			//联系我们
			var conhtml="";
			for(var i=0;i<con.length;i++){
				conhtml+="<li class='col-md-4 col-sm-6 col-lg-4 col-xs-6'>"+
						"<div class='licon'>"+
							"<span class='lxrxx'>"+
								"<h4>"+con[i].lxrname+"</h4>"+
								"<span class='tel'><i></i><a href='tel:"+con[i].phone+"'>"+con[i].phone+"</a></span>"+
							"</span>"+
							
							"<span class='ewm hidden-sm hidden-xs'><img src='"+con[i].lxr_imgurl+"' alt='' /></span>"+
							"<p class='hidden-sm hidden-xs'>微信二维码</p>"+
							"<div class='clear'></div>"+
						"</div>"+
					"</li>"
			}
				conhtml+="<div class='clear'></div>";
				$(".conuslist").html(conhtml);
			
			
			
			  var mySwiper = new Swiper('.swiper-container',{
				    loop: true,
				    mode:'horizontal',
				    resizeReInit : true,
				    calculateHeight : true,
				    autoplay: 0,
				    speed: 300,
				   	pagination : '.pagination',
					createPagination :true,
					
					onInit: function(swiper){
				      swiper.swipeNext()
				    }
				    //其他设置
				});  
					$('.swiper-button-prev').click(function(){
						mySwiper.swipePrev(); 
					})
					$('.swiper-button-next').click(function(){
						mySwiper.swipeNext(); 
					})
					var mySwiper1 = new Swiper('.swiper-container1',{
					    loop: true,
					    mode:'horizontal',
					    resizeReInit : true,
					    calculateHeight : true,
					    autoplay: 0,
					    speed: 300,
					   	pagination : '.pagination2',
						createPagination :true,
						});  
				

		},
		complete: function(data) {},
		error: function() {}
		
	});
		

	
})




