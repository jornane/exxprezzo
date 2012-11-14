<div id="producttree">
	<ul>
		<!-- FOR contents -->
		<li id="{contents.id}">
			<span class="expandable" data-url="{contents.href}">{contents.name}</span>
			<ul class="contents">
				
			</ul>
		</li>
		<!-- /FOR contents -->
	</ul>
</div>

<script src="http://code.jquery.com/jquery-1.8.2.min.js" type="text/javascript"></script>
<script>

	$('.expandable').click(expandGroup);

	function expandGroup()
	{
		$(this).parent().toggleClass('expanded');
		
		if(!$(this).hasClass('loaded'))
		{
			$(this).addClass('loaded');
			var id = $(this).parent().attr("id");
			var url = $(this).attr("data-url");
			getWebgroup(url, id);
		}
		
		console.log($(this));
		console.log($(this).parent());
	};

	function getWebgroup(url, id)
	{
		$.getJSON(url, function(data)
		{
			parseJSON(data, id);
		});
	}
	
	function parseJSON(JSON, id)
	{
		var html = '';
	
		$.each(JSON.webgroup.contents, function(i, content)
		{
			if(content.contents != null)
			{
				html += '<li id="'+content.id+'">';
				html += '<span class="expandable" data-url="?/producttree/json/webgroup/'+content.id+'">' + content.name + '</span>';
				html += '<ul class="contents" id="'+content.id+'">';
				html += '</ul>';
				html += '</li>';
			}
		});
		
		html += '';
		
		$("#" + id + " .contents").html(html);
		$("#" + id + " li .expandable").click(expandGroup);
	}
	
</script>