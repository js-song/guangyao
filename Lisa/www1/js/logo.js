$.ajax({
		type: "post",
		url: "http://110.249.185.45:8094/app/website/website_one.php?action=main",
		async:true,
		dataType:"json",
		success: function(res) {
			// ==== 缓存logo对象
			var navData = res.data.logo;
			//logo
			var logo = navData.logo_imgurl;
			// ==== 获取公司名称
			var company = navData.company;
			var logoimg="<img src='"+logo+"'alt='' />";
			$("#icon").attr("href",logo);
			$(".logoimg").html(logoimg);
			// ==== 填充公司名称
			$(".company").html(company);
			//版权
			var copyright=res.data.logo.copyright_logo;
			var copyrighthtml="<p>"+copyright+"</p>";
			$("footer>.container").html(copyrighthtml);
		},
		complete: function(data) {
			setTimeout(function() {
				$(".songmask").remove();
			}, 1000);
		},
		error: function() {
			
		}
		
	});
		