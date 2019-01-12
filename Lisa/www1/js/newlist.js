var config = new Config();

$(function(){

	//news
	var nowpage = 1;
	var allPage;
	
	function changePage(nowpage){
		$.ajax({
			type: config.newslist.type,
			url: config.newslist.url,
			data: {
				action:"news_list",
				page: nowpage
			},
			async:true,//异步
			dataType:"json",
			beforeSend: function() {
				$(".newslist").html("").text("正在加载中......");
			},
			success: function(res) {

				if(res.status != 1) {return};
				
				var newdata = res.data.newsdata;
				var newslisthtm = " ";
				
				var allpage=res.data.totalPage;
				var dqpage=res.data.page;
				var pagehtm=allpage;
				
				$(".lastli").html(pagehtm);
				
				for (var i=0;i<newdata.length;i++){
					if(newdata[i].imgurl == ""){
						newslisthtm+="<a href='newsxq.html?a="+newdata[i].newsid+ " ' target=''>"+
							"<li class='nopic'>"+
								"<dl>"+
									"<dd>"+
										"<h4>"+newdata[i].articletitle+"</h4>"+
										"<p>"+newdata[i].editorValue+"</p>"+
										"<time>"+newdata[i].addtime+"</time>"+
									"</dd>"+
								"</dl>"+
							"</li>"+
						"</a>"
					}else{
						newslisthtm+="<a href='newsxq.html?a="+newdata[i].newsid+ " ' target=''>"+
							"<li class='havepic'>"+
								"<dl>"+
									"<dt><img src=' "+newdata[i].imgurl+ " ' alt='' /></dt>"+
									"<dd>"+
										"<h4>"+newdata[i].articletitle+"</h4>"+
										"<p>"+newdata[i].editorValue+"</p>"+
										"<time>"+newdata[i].addtime+"</time>"+
									"</dd>"+
								"</dl>"+
							"</li>"+
						"</a>"
					}

					$(".newslist").html(newslisthtm);
					newslisthtm+="<div class='clear'></div>";
				}
				
				
				var onPagechange = function(page){
				    changePage(page);
				    
				}
				var allnum = parseInt(res.data.num);
				var obj = 
					pagination.init({
					    wrapid:'wrap1', //页面显示分页器容器id
					    total:allnum,//总条数
					    pagesize:8,//每页显示4条
					    currentPage:res.data.page,//当前页
					    onPagechange:onPagechange,
					    btnCount:7  //页数过多时，显示省略号的边界页码按钮数量，可省略，且值是大于5的奇数
					});			
				
			},
			complete: function(data) {},
			error: function() {}
		});
	}
	changePage(1);
	$("#pagination-prev").click(function() {
		if (nowpage <= 1) {	nowpage = 1; return;}
		else{nowpage --;}
		changePage(nowpage);
	});

	$("#pagination-next").click(function() {
		if (nowpage >= allPage) {nowpage = allPage;return false;}
		else{nowpage ++;}
		changePage(nowpage);
	});
	
})
