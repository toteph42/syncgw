function sgwSetSize(f,c){var e,g,d;if(!f){if(typeof(window.innerWidth)=="number"){f=window.innerHeight-30;c=window.innerWidth-20}else{if(document.documentElement&&document.documentElement.clientHeight){f=document.documentElement.clientHeight-45;c=document.documentElement.clientWidth-20}else{if(document.body&&document.body.clientHeight){f=document.body.clientHeight-45;c=document.body.clientWidth-20}}}}for(var b=new Array("sgwTit","sgwCmd","sgwBut","sgwMsg"),e=0;e<b.length;e++){if((d=document.getElementById(b[e]))!=null){if(b[e]=="sgwTit"||b[e]=="sgwBut"){d.style.width=c+"px"}else{d.style.width=(c-12)+"px"}}}d=document.getElementsByTagName("div");for(e=0;e<d.length;e++){if(d[e].className=="sgwDiv"&&d[e].id!="sgwMsg"){f-=(d[e].offsetHeight+parseInt(sgwPix(d[e].style.marginTop))+parseInt(sgwPix(d[e].style.marginBottom)))}}if((d=document.getElementById("sgwMsg"))!=null){d.style.height=f+"px"}}function sgwMaximize(g){var c,d,b,a;if(typeof(window.innerWidth)=="number"){d=window.innerHeight-30;a=window.innerWidth-20}else{if(document.documentElement&&document.documentElement.clientHeight){d=document.documentElement.clientHeight-45;a=document.documentElement.clientWidth-20}else{if(document.body&&document.body.clientHeight){d=document.body.clientHeight-45;a=document.body.clientWidth-20}}}var f=document.getElementsByTagName("div");for(c=0;c<f.length;c++){if(f[c].id=="sgwTit"||f[c].id=="sgwHead"){d-=(f[c].offsetHeight+parseInt(sgwPix(f[c].style.marginTop))+parseInt(sgwPix(f[c].style.marginBottom)))}}if((b=document.getElementById(g))!=null){b.style.height=d+"px"}}function sgwPix(a){if(a.length==0||a=="NaN"){return"0"}var b=a.indexOf("pt");if(b>-1){return a.slice(0,b)*0.72}b=a.indexOf("px");if(b>-1){return a.slice(0,b)}return a}function sgwPick(f,h,b,a,d){var c=0;while(true){var g=document.getElementById("ExpRow"+c);if(g==null){break}if(c==h){g.style.backgroundColor="#E6E6E6";document.getElementById("ExpHID").value=b;document.getElementById("ExpGRP").value=a;document.getElementById("ExpGID").value=d;if(f){document.getElementById("Action").value="Explorer";document.getElementById("ExpCmd").value=f;sgwAjaxStop(1);document.syncgw.submit()}}else{g.style.backgroundColor="#FFFFFF"}c++}}function sgwAdmin(d){var b=document.getElementById("AdminFlag");var c=document.getElementById("UserID");var e=document.getElementById("UserPW");if(d==1){b.checked=true}else{if(d==0){b.checked=false}}if(b.checked){c.value=null;c.disabled=true;c.style.backgroundColor="#EBEBE4";e.value=null;e.focus()}else{c.disabled=false;c.style.backgroundColor="#FFFFFF";c.focus()}};