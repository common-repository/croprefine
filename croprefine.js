// TopShelf - Popover ~ Copyright (c) 2011 - 2012 TopShelf Solutions Limited, https://github.com/flashbackzoo/TopShelf-Popover
// Released under MIT license, http://www.opensource.org/licenses/mit-license.php
var cur;!function(t){t.fn.tsPopover=function(n,e,o){var r=[],a={fade:{tranIn:function(n){c.positionPopover(n),t(n.container).clearQueue().stop().fadeTo("slow",1,function(){c.openCallback(n)})},tranOut:function(n){t(n.container).clearQueue().stop().fadeTo("fast",0,function(){c.closeCallback(n)})}}},i={open:function(n){t("[data-ui*='popover-trigger'][href='"+n.container.id+"']").addClass("current"),t(n.container).addClass("current"),a[n.settings.transition].tranIn(n)},close:function(n){t("[data-ui*='popover-trigger'].current").removeClass("current"),t(n.container).removeClass("current");try{a[n.settings.transition].tranOut(n)}catch(e){}}},c={openCallback:function(t){void 0!==t.settings.callbacks.open&&t.settings.callbacks.open(t)},closeCallback:function(t){void 0!==t.settings.callbacks.close&&t.settings.callbacks.close(t)},getPopoverObjectById:function(n){var e={};return t(r).each(function(o,r){currentContainerId=t(r.container).attr("id"),currentContainerId===n&&(e=r)}),e},positionPopover:function(n){var e=t(cur).closest("td").offset();t(n.container).css({top:e.top+"px",left:e.left+"px",marginLeft:"-362px"})}};if(i[n]){var s={container:this,settings:{transition:e,callbacks:{open:o,close:o}}};return i[n].call(i,s,s)}if("object"==typeof n||void 0===n){var l=t.extend({transition:"simple",easyClose:!1,draggable:!1,mask:!1,callbacks:{open:function(){return!1},close:function(){return!1}}},n);return this.each(function(){var n={container:this,settings:l,triggers:t("a[href='"+this.id+"']"),close:t(this).find("[data-ui*='popover-close']")[0]},e=function(e){var o={};return function(){o.triggers=function(){n.triggers.length>0&&t(n.triggers).each(function(){t(this).click(function(t){t.preventDefault(),t.stopPropagation()}),t(this).hover(function(o){if(cur=t(this),o.preventDefault(),o.stopPropagation(),!t(n.container).hasClass("current")){var a=t("[data-ui*='popover-panel'][class='current']");a.length&&t(r).each(function(t,n){e.close(n)}),e.open(n)}},function(){e.close(n)})})}}(),o};!function(){var t=e(i,c);t.triggers(),r[r.length]=n}()})}t.error(this)}}(jQuery);


/* croprefine handlers */
var cropdata = {};
var cropitem = {};

jQuery(document).ready( function($) {

	$(".modal-cropper-hide").on("click", hideModal);
	
	//choose an image to refine
	function cropRefine(item){
		$.post(ajaxurl, {'action': 'getimage','id':item }, 
		    function(r){			//console.log(r);
				//remove old sizes & destroy old cropper
		        $("#sizes tr").remove();
		        $(".cropper, .upload").unbind();
		        $(".container img").cropper("destroy");
		        var missing = 0;
		        /**************************************************************************************************
		        *	0: imagefile on disk
		        *	1: image width (px)
		        *	2: image height (px)
		        *	3: imagesize name
		        *	4: missing (true/false)
		        **************************************************************************************************/
				$.each( r.sizes, function( key, obj ){
					var itemname = (obj.hasOwnProperty("name") ? obj.name : obj.width+"x"+obj.height);
					var itemdesc = itemname + " (" + obj.size + ")";
					if(obj.exists<0) missing++;
					var listitem = '<tr rel="crop_'+key+'" name="' + obj.name + '" data-size="' + obj.size + '" data-width="' + obj.width + '" data-height="' + obj.height + '">';
						listitem+= '<td'+((obj.exists<0)?' class="missing"':'')+'><a class="preview" rel="' + obj.url + '" title="Preview of: ' + itemdesc + '" href="popover" data-ui="popover-trigger"><small>' + obj.size + "</small>" + itemname + '</a></td>';
						listitem+= '<td'+((obj.exists<0)?' class="missing"':'')+'>' + obj.width + ' x ' + obj.height + '</td>';
						listitem+= '<td><a class="cropper button button-primary button-large">Re-crop</a> ';
						listitem+= '<a class="upload button button-large">Upload</a></td></tr>';
					$("#sizes").append(listitem);
				});
				$("#available-sizes").show();

				$("div[data-ui='popover-panel']").tsPopover({
					"transition" : "fade"
				});
				
				$("#cropperimage").html("<img src="+r.image+" />");
				
				//any missing?
				$("div.missing").html((missing > 0 ? "<strong>Missing:</strong> "+missing+" image"+(missing==1?" was":"s were")+" not found on disk, as <span class='missing'>indicated below</span>.<br />It's recommended you re-crop (or upload) this image size to make it available to your theme.<br /><br />" : ""));
				
				//init the cropper
				initCropper();

				//add event listeners
				addListeners();
				showModal();
		    }
		);
	}
	
	//adds listeners to the file & crop links
	function addListeners(){
		
		$(".upload").on("click",function(){
			if($(this).closest("tr").next("tr").hasClass("filefield")){
				$(this).html("Upload");
				$(".filefield").remove();
			} else {
				var w = $(this).closest("tr").data('width'),
					h = $(this).closest("tr").data('height'),
					item = $(this).closest("tr").attr('rel'),
					size = $(this).closest("tr").data('size');
			
				$(this).html("Cancel");
				$(".filefield").remove();
				var formhtml = "<tr class='alternate filefield'><td colspan='2'>Please select a <strong>"+w+"</strong>px (width) x <strong>"+h+"</strong>px (height) image to be uploaded to replace this media item size. If the uploaded image's dimensions do not match, it will automatically be re-sized for you to "+w+"x"+h+".";
					formhtml+= "<input type='file' name='newimage' id='newimage' /></td><td>";
				    formhtml+= "<input type='hidden' name='cropitem[w]' value='"+w+"' />";
				    formhtml+= "<input type='hidden' name='cropitem[h]' value='"+h+"' />";
				    formhtml+= "<input type='hidden' name='cropitem[item]' value='"+item+"' />";
				    formhtml+= "<input type='hidden' name='cropitem[id]' value='"+mediaitem+"' />";
				    formhtml+= "<input type='hidden' name='cropitem[size]' value='"+size+"' />";
				    formhtml+= "<input type='submit' name='upload' id='upload' value='upload' class='button button-primary button-large' /></td></tr>";
				$(formhtml).insertAfter($(this).closest('tr'));
				
				//highlight row
				var uploadrow = $(this).parents("tr").attr("rel"); console.log("Upload Row: "+uploadrow);
				$(".cropper").each(function(){
					var row = $(this).parents("tr");
					if(row.attr("rel") == uploadrow) { 
						row.addClass("highlight"); 
					} else { row.removeClass("highlight"); }
				});
				//reset the cropper to this aspect, just in case the user cancels the upload
				$(".container > img").cropper("setAspectRatio", w/h);
				$(".container > img").cropper("reset", true);
				console.log("Setting up aspect for: "+w+"x"+h+" = "+w/h);
			}	
		});
		
		$(".cropper").on("click",function(){
			//remove filefields
			$(".filefield").remove();
			$("#upload").css("display","none");
			cropitem = { item:$(this).closest("tr").attr("rel"), 
							w:$(this).closest("tr").data("width"), 
						 size:$(this).closest("tr").data("size"), 
							h:$(this).closest("tr").data("height") }

			//$.fn.cropper.setDefaults(
			$(".container > img").cropper("setAspectRatio", cropitem.w/cropitem.h);
			$(".container > img").cropper("reset", true);
			//console.log("Setting up aspect for: "+cropitem.w+"x"+cropitem.h+" = "+cropitem.w/cropitem.h);
			
			//highlight row
			var croprow = $(this).parents("tr").attr("rel");
			$(".cropper").each(function(){
				var row = $(this).parents("tr");
				if(row.attr("rel") == croprow) { 
					row.addClass("highlight"); 
				} else { row.removeClass("highlight"); }
			});
		});
		
		$("#savecrop").on("click",function(){
			$.post(ajaxurl, {action:'cropimage', id:mediaitem, cropitem:cropitem, cropdata:cropdata  }, 
			    function(r){			//console.log(r);
					if(r.err < 0) { 
						$(".results").html("<strong>Error: </strong> Couldn't refine crop: "+r.msg); 
					} else { 
						console.log(r.w);
						$(".results").html("<strong>Success: </strong>"+r.w+"x"+r.h+" crop has been refined.<br />Clear your browser's cache and click the image name to see a preview."); 
					}
			    }
			);
		});
		
		$(".preview").on("mouseover",function(){
			$("#popover-preview").css({background: "url("+$(this).attr("rel")+"?"+Math.round(Math.random()*1000000)+") no-repeat center center", backgroundSize: "contain"});
			$("#popover p small").html($(this).attr("title"));
		});
		
		//pre-select first crop
		$(".cropper").first().click();
	}
	
	function showModal(){
		$("#modal-cropper, .media-modal-backdrop").show();
	};
	function hideModal(){
		$("#modal-cropper, .media-modal-backdrop").hide();
	};

	//init cropper
	function initCropper(){
		$(".container img").cropper({
			done: function(data) {
			    cropdata = data;
			},
			built: function(){
				$("#cropperimage img").css("opacity",1);
			}
		});
	};
	
	initCropper();
	
	if( typeof(mediaitem)!="undefined" ) cropRefine(mediaitem);
	
	//donate form
	var $donateform = jQuery("div.donate").html();
	jQuery("div.donate").html("<form action='https://www.paypal.com/cgi-bin/webscr' method='post' id='donate' target='_blank'>"+$donateform+"</form>").css("display","block");

});

function getBackLink(el){
	try {
		var curUrl = new URL(document.location);
		var curPost = curUrl.searchParams.get("post");
		console.log(curPost);
		if(!isNaN(parseInt(curPost))){
			el.href+="&post="+curPost;
		}
	} catch(err){
		return;
	}
}

	
