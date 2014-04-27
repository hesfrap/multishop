(function(){window.mcFileManager={settings:{document_base_url:"",relative_urls:false,remove_script_host:false,use_url_path:true,remember_last_path:"auto",target_elements:"",target_form:"",handle:"file"},setup:function(){var e=this,h,g=document,f=[];h=g.location.href;if(h.indexOf("?")!=-1){h=h.substring(0,h.indexOf("?"))}h=h.substring(0,h.lastIndexOf("/")+1);e.settings.default_base_url=unescape(h);function c(d){var j,k;for(j=0;j<d.length;j++){k=d[j];f.push(k);if(k.src&&/mcfilemanager\.js/g.test(k.src)){return k.src.substring(0,k.src.lastIndexOf("/"))}}}h=g.documentElement;if(h&&(h=c(h.getElementsByTagName("script")))){return e.baseURL=h}h=g.getElementsByTagName("script");if(h&&(h=c(h))){return e.baseURL=h}h=g.getElementsByTagName("head")[0];if(h&&(h=c(h.getElementsByTagName("script")))){return e.baseURL=h}},relaxDomain:function(){var c=this,d=/(http|https):\/\/([^\/:]+)\/?/.exec(c.baseURL);if(window.tinymce&&tinymce.relaxedDomain){c.relaxedDomain=tinymce.relaxedDomain;return}if(d&&d[2]!=document.location.hostname){document.domain=c.relaxedDomain=d[2].replace(/.*\.(.+\..+)$/,"$1")}},init:function(c){this.extend(this.settings,c)},browse:function(d){var c=this;d=d||{};if(d.fields){d.oninsert=function(e){c.each(d.fields.replace(/\s+/g,"").split(/,/),function(f){var g;if(g=document.getElementById(f)){c._setVal(g,e.focusedFile.url)}})}}this.openWin({page:"index.html"},d)},edit:function(c){this.openWin({page:"edit.html",width:800,height:500},c)},upload:function(c){this.openWin({page:"upload.html",width:550,height:350},c)},createDoc:function(c){this.openWin({page:"createdoc.html",width:450,height:280},c)},createDir:function(c){this.openWin({page:"createdir.html",width:450,height:280},c)},createZip:function(c){this.openWin({page:"createzip.html",width:450,height:280},c)},openWin:function(i,d){var h=this,e,c;h.windowArgs=d=h.extend({},h.settings,d);i=h.extend({x:-1,y:-1,width:800,height:500,inline:1},i);if(i.x==-1){i.x=parseInt(screen.width/2)-(i.width/2)}if(i.y==-1){i.y=parseInt(screen.height/2)-(i.height/2)}if(i.page){i.url=h.baseURL+"/../index.php?type=fm&page="+i.page}if(d.session_id){i.url+="&sessionid="+d.session_id}if(d.custom_data){i.url+="&custom_data="+escape(d.custom_data)}if(h.relaxedDomain){i.url+="&domain="+escape(h.relaxedDomain)}if(d.custom_query){i.url+=d.custom_query}if(d.target_frame){if(e=frames[d.target_frame]){e.document.location=i.url}if(e=document.getElementById(d.target_frame)){e.src=i.url}return}if(window.tinymce&&tinyMCE.activeEditor){return tinyMCE.activeEditor.windowManager.open(i,d)}if(window.jQuery&&jQuery.WindowManager){return jQuery.WindowManager.open(i,d)}c=window.open(i.url,"mcFileManagerWin","left="+i.x+",top="+i.y+",width="+i.width+",height="+i.height+",scrollbars="+(i.scrollbars?"yes":"no")+",resizable="+(i.resizable?"yes":"no")+",statusbar="+(i.statusbar?"yes":"no"));try{c.focus()}catch(g){}},each:function(g,e,d){var h,c;if(g){d=d||g;if(g.length!==undefined){for(h=0,c=g.length;h<c;h++){e.call(d,g[h],h,g)}}else{for(h in g){if(g.hasOwnProperty(h)){e.call(d,g[h],h,g)}}}}},extend:function(){var e,c=arguments,g=c[0],f,d;for(f=1;f<c.length;f++){if(d=c[f]){for(e in d){g[e]=d[e]}}}return g},open:function(i,e,d,c,h){var f=this,g;h=h||{};if(!h.url&&document.forms[i]&&(g=document.forms[i].elements[e.split(",")[0]])){h.url=g.value}if(!c){h.oninsert=function(n){var m,k,j,l=n.focusedFile;j=e.replace(/\s+/g,"").split(",");for(k=0;k<j.length;k++){if(m=document.forms[i][j[k]]){f._setVal(m,l.url)}}}}else{if(typeof(c)=="string"){c=window[c]}h.oninsert=function(j){c(j.focusedFile.url,j)}}f.browse(h)},filebrowserCallBack:function(h,k,d,j,e){var l=mcFileManager,f,c,g,m={};if(window.mcImageManager&&!e){c=mcImageManager.settings.handle;c=c.split(",");for(f=0;f<c.length;f++){if(d==c[f]){g=1}}if(g&&mcImageManager.filebrowserCallBack(h,k,d,j,1)){return}}l.each(tinyMCE.activeEditor?tinyMCE.activeEditor.settings:tinyMCE.settings,function(n,i){if(i.indexOf("filemanager_")===0){m[i.substring(12)]=n}});l.browse(l.extend(m,{url:j.document.forms[0][h].value,relative_urls:0,oninsert:function(q){var p,n,i;p=j.document.forms[0];n=q.focusedFile.url;inf=q.focusedFile.custom;if(typeof(TinyMCE_convertURL)!="undefined"){n=TinyMCE_convertURL(n,null,true)}else{if(tinyMCE.convertURL){n=tinyMCE.convertURL(n,null,true)}else{n=tinyMCE.activeEditor.convertURL(n,null,true)}}if(inf.custom&&inf.custom.description){i=["alt","title","linktitle"];for(f=0;f<i.length;f++){if(p.elements[i[f]]){p.elements[i[f]].value=inf.custom.description}}}l._setVal(p[h],n);j=null}}));return true},_setVal:function(f,c){f.value=c;try{f.onchange()}catch(d){}}};mcFileManager.setup();mcFileManager.relaxDomain();var b={getInfo:function(){return{longname:"MCFileManager PHP",author:"Moxiecode Systems AB",authorurl:"http://tinymce.moxiecode.com",infourl:"http://tinymce.moxiecode.com/plugins_filemanager.php",version:"3.1.2.3"}},convertURL:function(c){if(window.TinyMCE_convertURL){return TinyMCE_convertURL(c,null,true)}if(tinyMCE.convertURL){return tinyMCE.convertURL(c,null,true)}return tinyMCE.activeEditor.convertURL(c,null,true)},replace:function(g,k,j){var f,h;if(typeof(g)!="string"){return g(k,j)}function c(i,e){for(f=0,h=i,e=e.split(".");f<e.length;f++){h=h[e[f]]}return h}g=""+g.replace(/\{\$([^\}]+)\}/g,function(i,d){var e=d.split("|"),m=c(k,e[0]);if(e.length==1&&j&&j.xmlEncode){m=j.xmlEncode(m)}for(f=1;f<e.length;f++){m=j[e[f]](m,k,d)}return m});g=g.replace(/\{\=([\w]+)([^\}]+)\}/g,function(e,d,i){return c(j,d)(k,d,i)});return g}};if(window.tinymce){tinymce.create("tinymce.plugins.FileManagerPlugin",{init:function(c,d){var e=this;e.editor=c;e.url=d;mcFileManager.baseURL=d+"/js";c.settings.file_browser_callback=mcFileManager.filebrowserCallBack;mcFileManager.settings.handle=c.getParam("filemanager_handle",mcFileManager.settings.handle);c.addCommand("mceInsertFile",function(g,f){var h={};tinymce.each(tinyMCE.activeEditor.settings,function(j,i){if(i.indexOf("filemanager_")===0){h[i.substring(12)]=j}});mcFileManager.browse(tinymce.extend(h,{oninsert:function(k){var i,j;if(j=c.windowManager.bookmark){c.selection.moveToBookmark(j)}if(!c.selection.isCollapsed()){c.execCommand("createlink",false,"javascript:mce_temp_url();");tinymce.grep(c.dom.select("a"),function(l){if(l.href=="javascript:mce_temp_url();"){i=b.convertURL(k.focusedFile.url);l.href=i;c.dom.setAttrib(l,"mce_href",i)}})}else{c.execCommand("mceInsertContent",false,b.replace(c.getParam("filemanager_insert_template",'<a href="{$url}">{$name}</a>'),k.focusedFile,{urlencode:function(l){return escape(l)},xmlEncode:function(l){return tinymce.DOM.encode(l)}}))}}},f))})},getInfo:function(){return b.getInfo()},createControl:function(i,d){var g=this,h,f=g.editor,e;switch(i){case"insertfile":e=f.getParam("filemanager_insert_template");if(e instanceof Array){h=d.createMenuButton("insertfile",{title:"filemanager_insertfile_desc",image:g.url+"/pages/fm/img/insertfile.gif",icons:false});h.onRenderMenu.add(function(k,j){tinymce.each(e,function(c){j.add({title:c.title,onclick:function(){f.execCommand("mceInsertFile",false,c)}})})})}else{h=d.createButton("insertfile",{title:"filemanager_insertfile_desc",image:g.url+"/pages/fm/img/insertfile.gif",onclick:function(){f.execCommand("mceInsertFile",false,{template:e})}})}return h}return null}});tinymce.PluginManager.add("filemanager",tinymce.plugins.FileManagerPlugin);tinymce.ScriptLoader.load((tinymce.PluginManager.urls.filemanager||tinymce.baseURL+"/plugins/filemanager")+"/language/index.php?type=fm&format=tinymce_3_x&group=tinymce&prefix=filemanager_")}if(window.TinyMCE_Engine){var a={setup:function(){var c=(window.realTinyMCE||tinyMCE).baseURL;mcFileManager.baseURL=c+"/plugins/filemanager/js";document.write('<script type="text/javascript" src="'+c+'/plugins/filemanager/language/index.php?type=fm&format=tinymce&group=tinymce&prefix=filemanager_"><\/script>')},initInstance:function(c){c.settings.file_browser_callback="mcFileManager.filebrowserCallBack";mcFileManager.settings.handle=tinyMCE.getParam("filemanager_handle",mcFileManager.settings.handle)},getControlHTML:function(c){switch(c){case"insertfile":return tinyMCE.getButtonHTML(c,"lang_filemanager_insertfile_desc","{$pluginurl}/pages/fm/img/insertfile.gif","mceInsertFile",false)}return""},getInfo:function(){return b.getInfo()},execCommand:function(h,e,g,f,d){var c=tinyMCE.getInstanceById(h);if(g=="mceInsertFile"){mcFileManager.browse(tinyMCE.extend({path:tinyMCE.getParam("filemanager_path"),rootpath:tinyMCE.getParam("filemanager_rootpath"),remember_last_path:tinyMCE.getParam("filemanager_remember_last_path"),custom_data:tinyMCE.getParam("filemanager_custom_data"),insert_filter:tinyMCE.getParam("filemanager_insert_filter"),oninsert:function(m){var k,j,l;if(!c.selection.isCollapsed()){c.execCommand("createlink",false,"javascript:mce_temp_url();");j=tinyMCE.selectElements(c.getBody(),"A",function(i){return tinyMCE.getAttrib(i,"href")=="javascript:mce_temp_url();"});for(l=0;l<j.length;l++){k=b.convertURL(m.focusedFile.url);j[l].href=k;j[l].setAttribute("mce_href",k)}}else{c.execCommand("mceInsertContent",false,b.replace(tinyMCE.getParam("filemanager_insert_template",'<a href="{$url}">{$name}</a>'),m.focusedFile,{urlencode:function(i){return escape(i)},xmlEncode:function(i){return tinyMCE.xmlEncode(i)}}))}}},d));return true}return false}};a.setup();tinyMCE.addPlugin("filemanager",a)}})();