
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: LIB.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2026 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

/*!
 * enquire.js v2.1.0 - Awesome Media Queries in JavaScript
 * Copyright (c) 2013 Nick Williams - http://wicky.nillia.ms/enquire.js
 * License: MIT (http://www.opensource.org/licenses/mit-license.php)
 */
(function(t,i,n){var e=i.matchMedia;"undefined"!=typeof module&&module.exports?module.exports=n(e):"function"==typeof define&&define.amd?define(function(){return i[t]=n(e)}):i[t]=n(e)})("enquire",this,function(t){"use strict";function i(t,i){var n,e=0,s=t.length;for(e;s>e&&(n=i(t[e],e),n!==!1);e++);}function n(t){return"[object Array]"===Object.prototype.toString.apply(t)}function e(t){return"function"==typeof t}function s(t){this.options=t,!t.deferSetup&&this.setup()}function o(i,n){this.query=i,this.isUnconditional=n,this.handlers=[],this.mql=t(i);var e=this;this.listener=function(t){e.mql=t,e.assess()},this.mql.addListener(this.listener)}function r(){if(!t)throw Error("matchMedia not present, legacy browsers require a polyfill");this.queries={},this.browserIsIncapable=!t("only all").matches}return s.prototype={setup:function(){this.options.setup&&this.options.setup(),this.initialised=!0},on:function(){!this.initialised&&this.setup(),this.options.match&&this.options.match()},off:function(){this.options.unmatch&&this.options.unmatch()},destroy:function(){this.options.destroy?this.options.destroy():this.off()},equals:function(t){return this.options===t||this.options.match===t}},o.prototype={addHandler:function(t){var i=new s(t);this.handlers.push(i),this.matches()&&i.on()},removeHandler:function(t){var n=this.handlers;i(n,function(i,e){return i.equals(t)?(i.destroy(),!n.splice(e,1)):void 0})},matches:function(){return this.mql.matches||this.isUnconditional},clear:function(){i(this.handlers,function(t){t.destroy()}),this.mql.removeListener(this.listener),this.handlers.length=0},assess:function(){var t=this.matches()?"on":"off";i(this.handlers,function(i){i[t]()})}},r.prototype={register:function(t,s,r){var h=this.queries,u=r&&this.browserIsIncapable;return h[t]||(h[t]=new o(t,u)),e(s)&&(s={match:s}),n(s)||(s=[s]),i(s,function(i){h[t].addHandler(i)}),this},unregister:function(t,i){var n=this.queries[t];return n&&(i?n.removeHandler(i):(n.clear(),delete this.queries[t])),this}},new r});

/*
 * jQuery Textarea Characters Counter Plugin v 2.0
 * Examples and documentation at: http://roy-jin.appspot.com/jsp/textareaCounter.jsp
 * Copyright (c) 2010 Roy Jin
 * Version: 2.0 (11-JUN-2010)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 * Requires: jQuery v1.4.2 or later
 */
(function(d){d.fn.textareaCount=function(b,q){function f(){g.html(t());"undefined"!=typeof q&&q.call(this,{input:e,max:h,left:m,words:k});return!0}function t(){var a=c.val(),n=a.length;if(0<b.maxCharacterSize){n>=b.maxCharacterSize&&(a=a.substring(0,b.maxCharacterSize));var l=r(a),d=b.maxCharacterSize-l;p()||(d=b.maxCharacterSize);if(n>d){var f=this.scrollTop;c.val(a.substring(0,d));this.scrollTop=f}g.removeClass(b.warningStyle);d-n<=b.warningNumber&&g.addClass(b.warningStyle);e=c.val().length+l;
p()||(e=c.val().length);k=s(c.val()).length-1;m=h-e}else l=r(a),e=c.val().length+l,p()||(e=c.val().length),k=s(c.val()).length-1;a=b.displayFormat;a=a.replace("#input",e);a=a.replace("#words",k);0<h&&(a=a.replace("#max",h),a=a.replace("#left",m));return a}function p(){return-1!=navigator.appVersion.toLowerCase().indexOf("win")?!0:!1}function r(a){for(var b=0,c=0;c<a.length;c++)"\n"==a.charAt(c)&&b++;return b}function s(a){a=(a+" ").replace(/^[^A-Za-z0-9]+/gi,"");var b=rExp=/[^A-Za-z0-9]+/gi;return a.replace(b,
" ").split(" ")}b=d.extend({maxCharacterSize:250,originalStyle:"textarea_counter_default",warningStyle:"textarea_counter_warning",warningNumber:20,displayFormat:"<b>#left</b> "+lang.characters_left},b);var c=d(this);d("<div class='charleft'>&nbsp;</div>").insertAfter(c);var g=c.next(".charleft");g.addClass(b.originalStyle);g.css({});var e=0,h=b.maxCharacterSize,m=0,k=0;c.bind("keyup",function(a){f()}).bind("mouseover",function(a){setTimeout(function(){f()},10)}).bind("paste",function(a){setTimeout(function(){f()},
10)})}})(jQuery);

/*
* Generated by CoffeeScript 1.4.0
*/
(function(){var e,n=[].indexOf||function(e){for(var d=0,g=this.length;d<g;d++)if(d in this&&this[d]===e)return d;return-1};e=jQuery;e.fn.validateCreditCard=function(x,d){var g,f,h,q,r,t,u,k,v,l,w,p;h=[{name:"amex",pattern:/^3[47]/,valid_length:[15]},{name:"diners_club_carte_blanche",pattern:/^30[0-5]/,valid_length:[14]},{name:"diners_club_international",pattern:/^36/,valid_length:[14]},{name:"jcb",pattern:/^35(2[89]|[3-8][0-9])/,valid_length:[16]},{name:"laser",pattern:/^(6304|670[69]|6771)/,valid_length:[16,17,18,19]},{name:"visa_electron",pattern:/^(4026|417500|4508|4844|491(3|7))/,valid_length:[16]},{name:"visa",pattern:/^4/,valid_length:[16]},{name:"mastercard",pattern:/^5[1-5]/,valid_length:[16]},{name:"maestro",pattern:/^(5018|5020|5038|6304|6759|676[1-3])/,valid_length:[12,13,14,15,16,17,18,19]},{name:"discover",pattern:/^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)/,valid_length:[16]}];null==d&&(d={});null==d.accept&&(d.accept=function(){var a,c,b;b=[];a=0;for(c=h.length;a<c;a++)g=h[a],b.push(g.name);return b}());p=d.accept;l=0;for(w=p.length;l<w;l++)if(f=p[l],0>n.call(function(){var a,c,b;b=[];a=0;for(c=h.length;a<c;a++)g=h[a],b.push(g.name);return b}(),f))throw"Credit card type '"+f+"' is not supported";q=function(a){var c,b,e,m;m=[];c=0;for(b=h.length;c<b;c++)g=h[c],(e=g.name,0<=n.call(d.accept,e))&&m.push(g);c=0;for(b=m.length;c<b;c++)if(f=m[c],a.match(f.pattern))return f;return null};t=function(a){var c,b,d,f,e;b=0;e=a.split("").reverse();c=d=0;for(f=e.length;d<f;c=++d)a=e[c],a=+a,c%2?(a*=2,b=10>a?b+a:b+(a-9)):b+=a;return 0===b%10};r=function(a,c){var b;return b=a.length,0<=n.call(c.valid_length,b)};v=function(a){var c,b;f=q(a);c=b=!1;null!=f&&(b=t(a),c=r(a,f));return x({card_type:f,luhn_valid:b,length_valid:c})};k=function(){var a;a=u(e(this).val());return v(a)};u=function(a){return a.replace(/[ -]/g,"")};this.bind("input",function(){e(this).unbind("keyup");return k.call(this)});this.bind("keyup",function(){return k.call(this)});0!==this.length&&k.call(this);return this}}).call(this);

/*
 * jQuery Templates Plugin 1.0.0pre
 * http://github.com/jquery/jquery-tmpl
 * Requires jQuery 1.4.2
 *
 * Copyright 2011, Software Freedom Conservancy, Inc.
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 */
(function(a){var r=a.fn.domManip,d="_tmplitem",q=/^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,b={},f={},e,p={key:0,data:{}},i=0,c=0,l=[];function g(g,d,h,e){var c={data:e||(e===0||e===false)?e:d?d.data:{},_wrap:d?d._wrap:null,tmpl:null,parent:d||null,nodes:[],calls:u,nest:w,wrap:x,html:v,update:t};g&&a.extend(c,g,{nodes:[],parent:d});if(h){c.tmpl=h;c._ctnt=c._ctnt||c.tmpl(a,c);c.key=++i;(l.length?f:b)[i]=c}return c}a.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(f,d){a.fn[f]=function(n){var g=[],i=a(n),k,h,m,l,j=this.length===1&&this[0].parentNode;e=b||{};if(j&&j.nodeType===11&&j.childNodes.length===1&&i.length===1){i[d](this[0]);g=this}else{for(h=0,m=i.length;h<m;h++){c=h;k=(h>0?this.clone(true):this).get();a(i[h])[d](k);g=g.concat(k)}c=0;g=this.pushStack(g,f,i.selector)}l=e;e=null;a.tmpl.complete(l);return g}});a.fn.extend({tmpl:function(d,c,b){return a.tmpl(this[0],d,c,b)},tmplItem:function(){return a.tmplItem(this[0])},template:function(b){return a.template(b,this[0])},domManip:function(d,m,k){if(d[0]&&a.isArray(d[0])){var g=a.makeArray(arguments),h=d[0],j=h.length,i=0,f;while(i<j&&!(f=a.data(h[i++],"tmplItem")));if(f&&c)g[2]=function(b){a.tmpl.afterManip(this,b,k)};r.apply(this,g)}else r.apply(this,arguments);c=0;!e&&a.tmpl.complete(b);return this}});a.extend({tmpl:function(d,h,e,c){var i,k=!c;if(k){c=p;d=a.template[d]||a.template(null,d);f={}}else if(!d){d=c.tmpl;b[c.key]=c;c.nodes=[];c.wrapped&&n(c,c.wrapped);return a(j(c,null,c.tmpl(a,c)))}if(!d)return[];if(typeof h==="function")h=h.call(c||{});e&&e.wrapped&&n(e,e.wrapped);i=a.isArray(h)?a.map(h,function(a){return a?g(e,c,d,a):null}):[g(e,c,d,h)];return k?a(j(c,null,i)):i},tmplItem:function(b){var c;if(b instanceof a)b=b[0];while(b&&b.nodeType===1&&!(c=a.data(b,"tmplItem"))&&(b=b.parentNode));return c||p},template:function(c,b){if(b){if(typeof b==="string")b=o(b);else if(b instanceof a)b=b[0]||{};if(b.nodeType)b=a.data(b,"tmpl")||a.data(b,"tmpl",o(b.innerHTML));return typeof c==="string"?(a.template[c]=b):b}return c?typeof c!=="string"?a.template(null,c):a.template[c]||a.template(null,q.test(c)?c:a(c)):null},encode:function(a){return(""+a).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;")}});a.extend(a.tmpl,{tag:{tmpl:{_default:{$2:"null"},open:"if($notnull_1){__=__.concat($item.nest($1,$2));}"},wrap:{_default:{$2:"null"},open:"$item.calls(__,$1,$2);__=[];",close:"call=$item.calls();__=call._.concat($item.wrap(call,__));"},each:{_default:{$2:"$index, $value"},open:"if($notnull_1){$.each($1a,function($2){with(this){",close:"}});}"},"if":{open:"if(($notnull_1) && $1a){",close:"}"},"else":{_default:{$1:"true"},open:"}else if(($notnull_1) && $1a){"},html:{open:"if($notnull_1){__.push($1a);}"},"=":{_default:{$1:"$data"},open:"if($notnull_1){__.push($.encode($1a));}"},"!":{open:""}},complete:function(){b={}},afterManip:function(f,b,d){var e=b.nodeType===11?a.makeArray(b.childNodes):b.nodeType===1?[b]:[];d.call(f,b);m(e);c++}});function j(e,g,f){var b,c=f?a.map(f,function(a){return typeof a==="string"?e.key?a.replace(/(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g,"$1 "+d+'="'+e.key+'" $2'):a:j(a,e,a._ctnt)}):e;if(g)return c;c=c.join("");c.replace(/^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/,function(f,c,e,d){b=a(e).get();m(b);if(c)b=k(c).concat(b);if(d)b=b.concat(k(d))});return b?b:k(c)}function k(c){var b=document.createElement("div");b.innerHTML=c;return a.makeArray(b.childNodes)}function o(b){return new Function("jQuery","$item","var $=jQuery,call,__=[],$data=$item.data;with($data){__.push('"+a.trim(b).replace(/([\\'])/g,"\\$1").replace(/[\r\t\n]/g," ").replace(/\$\{([^\}]*)\}/g,"{{= $1}}").replace(/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g,function(m,l,k,g,b,c,d){var j=a.tmpl.tag[k],i,e,f;if(!j)throw"Unknown template tag: "+k;i=j._default||[];if(c&&!/\w$/.test(b)){b+=c;c=""}if(b){b=h(b);d=d?","+h(d)+")":c?")":"";e=c?b.indexOf(".")>-1?b+h(c):"("+b+").call($item"+d:b;f=c?e:"(typeof("+b+")==='function'?("+b+").call($item):("+b+"))"}else f=e=i.$1||"null";g=h(g);return"');"+j[l?"close":"open"].split("$notnull_1").join(b?"typeof("+b+")!=='undefined' && ("+b+")!=null":"true").split("$1a").join(f).split("$1").join(e).split("$2").join(g||i.$2||"")+"__.push('"})+"');}return __;")}function n(c,b){c._wrap=j(c,true,a.isArray(b)?b:[q.test(b)?b:a(b).html()]).join("")}function h(a){return a?a.replace(/\\'/g,"'").replace(/\\\\/g,"\\"):null}function s(b){var a=document.createElement("div");a.appendChild(b.cloneNode(true));return a.innerHTML}function m(o){var n="_"+c,k,j,l={},e,p,h;for(e=0,p=o.length;e<p;e++){if((k=o[e]).nodeType!==1)continue;j=k.getElementsByTagName("*");for(h=j.length-1;h>=0;h--)m(j[h]);m(k)}function m(j){var p,h=j,k,e,m;if(m=j.getAttribute(d)){while(h.parentNode&&(h=h.parentNode).nodeType===1&&!(p=h.getAttribute(d)));if(p!==m){h=h.parentNode?h.nodeType===11?0:h.getAttribute(d)||0:0;if(!(e=b[m])){e=f[m];e=g(e,b[h]||f[h]);e.key=++i;b[i]=e}c&&o(m)}j.removeAttribute(d)}else if(c&&(e=a.data(j,"tmplItem"))){o(e.key);b[e.key]=e;h=a.data(j.parentNode,"tmplItem");h=h?h.key:0}if(e){k=e;while(k&&k.key!=h){k.nodes.push(j);k=k.parent}delete e._ctnt;delete e._wrap;a.data(j,"tmplItem",e)}function o(a){a=a+n;e=l[a]=l[a]||g(e,b[e.parent.key+n]||e.parent)}}}function u(a,d,c,b){if(!a)return l.pop();l.push({_:a,tmpl:d,item:this,data:c,options:b})}function w(d,c,b){return a.tmpl(a.template(d),c,b,this)}function x(b,d){var c=b.options||{};c.wrapped=d;return a.tmpl(a.template(b.tmpl),b.data,c,b.item)}function v(d,c){var b=this._wrap;return a.map(a(a.isArray(b)?b.join(""):b).filter(d||"*"),function(a){return c?a.innerText||a.textContent:a.outerHTML||s(a)})}function t(){var b=this.nodes;a.tmpl(null,null,null,this).insertBefore(b[0]);a(b).remove()}})(jQuery);

/*
 * jquery.qtip. The jQuery tooltip plugin
 *
 * Copyright (c) 2009 Craig Thompson
 * http://craigsworks.com
 *
 * Licensed under MIT
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Launch  : February 2009
 * Version : 1.0.0-rc3
 * Released: Tuesday 12th May, 2009 - 00:00
 * Debug: jquery.qtip.debug.js
 */
(function(f){f.fn.qtip=function(B,u){var y,t,A,s,x,w,v,z;if(typeof B=="string"){if(typeof f(this).data("qtip")!=="object"){f.fn.qtip.log.error.call(self,1,f.fn.qtip.constants.NO_TOOLTIP_PRESENT,false)}if(B=="api"){return f(this).data("qtip").interfaces[f(this).data("qtip").current]}else{if(B=="interfaces"){return f(this).data("qtip").interfaces}}}else{if(!B){B={}}if(typeof B.content!=="object"||(B.content.jquery&&B.content.length>0)){B.content={text:B.content}}if(typeof B.content.title!=="object"){B.content.title={text:B.content.title}}if(typeof B.position!=="object"){B.position={corner:B.position}}if(typeof B.position.corner!=="object"){B.position.corner={target:B.position.corner,tooltip:B.position.corner}}if(typeof B.show!=="object"){B.show={when:B.show}}if(typeof B.show.when!=="object"){B.show.when={event:B.show.when}}if(typeof B.show.effect!=="object"){B.show.effect={type:B.show.effect}}if(typeof B.hide!=="object"){B.hide={when:B.hide}}if(typeof B.hide.when!=="object"){B.hide.when={event:B.hide.when}}if(typeof B.hide.effect!=="object"){B.hide.effect={type:B.hide.effect}}if(typeof B.style!=="object"){B.style={name:B.style}}B.style=c(B.style);s=f.extend(true,{},f.fn.qtip.defaults,B);s.style=a.call({options:s},s.style);s.user=f.extend(true,{},B)}return f(this).each(function(){if(typeof B=="string"){w=B.toLowerCase();A=f(this).qtip("interfaces");if(typeof A=="object"){if(u===true&&w=="destroy"){while(A.length>0){A[A.length-1].destroy()}}else{if(u!==true){A=[f(this).qtip("api")]}for(y=0;y<A.length;y++){if(w=="destroy"){A[y].destroy()}else{if(A[y].status.rendered===true){if(w=="show"){A[y].show()}else{if(w=="hide"){A[y].hide()}else{if(w=="focus"){A[y].focus()}else{if(w=="disable"){A[y].disable(true)}else{if(w=="enable"){A[y].disable(false)}}}}}}}}}}}else{v=f.extend(true,{},s);v.hide.effect.length=s.hide.effect.length;v.show.effect.length=s.show.effect.length;if(v.position.container===false){v.position.container=f(document.body)}if(v.position.target===false){v.position.target=f(this)}if(v.show.when.target===false){v.show.when.target=f(this)}if(v.hide.when.target===false){v.hide.when.target=f(this)}t=f.fn.qtip.interfaces.length;for(y=0;y<t;y++){if(typeof f.fn.qtip.interfaces[y]=="undefined"){t=y;break}}x=new d(f(this),v,t);f.fn.qtip.interfaces[t]=x;if(typeof f(this).data("qtip")=="object"){if(typeof f(this).attr("qtip")==="undefined"){f(this).data("qtip").current=f(this).data("qtip").interfaces.length}f(this).data("qtip").interfaces.push(x)}else{f(this).data("qtip",{current:0,interfaces:[x]})}if(v.content.prerender===false&&v.show.when.event!==false&&v.show.ready!==true){v.show.when.target.bind(v.show.when.event+".qtip-"+t+"-create",{qtip:t},function(C){z=f.fn.qtip.interfaces[C.data.qtip];z.options.show.when.target.unbind(z.options.show.when.event+".qtip-"+C.data.qtip+"-create");z.cache.mouse={x:C.pageX,y:C.pageY};p.call(z);z.options.show.when.target.trigger(z.options.show.when.event)})}else{x.cache.mouse={x:v.show.when.target.offset().left,y:v.show.when.target.offset().top};p.call(x)}}})};function d(u,t,v){var s=this;s.id=v;s.options=t;s.status={animated:false,rendered:false,disabled:false,focused:false};s.elements={target:u.addClass(s.options.style.classes.target),tooltip:null,wrapper:null,content:null,contentWrapper:null,title:null,button:null,tip:null,bgiframe:null};s.cache={mouse:{},position:{},toggle:0};s.timers={};f.extend(s,s.options.api,{show:function(y){var x,z;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"show")}if(s.elements.tooltip.css("display")!=="none"){return s}s.elements.tooltip.stop(true,false);x=s.beforeShow.call(s,y);if(x===false){return s}function w(){if(s.options.position.type!=="static"){s.focus()}s.onShow.call(s,y);if(f.browser.msie){s.elements.tooltip.get(0).style.removeAttribute("filter")}}s.cache.toggle=1;if(s.options.position.type!=="static"){s.updatePosition(y,(s.options.show.effect.length>0))}if(typeof s.options.show.solo=="object"){z=f(s.options.show.solo)}else{if(s.options.show.solo===true){z=f("div.qtip").not(s.elements.tooltip)}}if(z){z.each(function(){if(f(this).qtip("api").status.rendered===true){f(this).qtip("api").hide()}})}if(typeof s.options.show.effect.type=="function"){s.options.show.effect.type.call(s.elements.tooltip,s.options.show.effect.length);s.elements.tooltip.queue(function(){w();f(this).dequeue()})}else{switch(s.options.show.effect.type.toLowerCase()){case"fade":s.elements.tooltip.fadeIn(s.options.show.effect.length,w);break;case"slide":s.elements.tooltip.slideDown(s.options.show.effect.length,function(){w();if(s.options.position.type!=="static"){s.updatePosition(y,true)}});break;case"grow":s.elements.tooltip.show(s.options.show.effect.length,w);break;default:s.elements.tooltip.show(null,w);break}s.elements.tooltip.addClass(s.options.style.classes.active)}return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_SHOWN,"show")},hide:function(y){var x;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"hide")}else{if(s.elements.tooltip.css("display")==="none"){return s}}clearTimeout(s.timers.show);s.elements.tooltip.stop(true,false);x=s.beforeHide.call(s,y);if(x===false){return s}function w(){s.onHide.call(s,y)}s.cache.toggle=0;if(typeof s.options.hide.effect.type=="function"){s.options.hide.effect.type.call(s.elements.tooltip,s.options.hide.effect.length);s.elements.tooltip.queue(function(){w();f(this).dequeue()})}else{switch(s.options.hide.effect.type.toLowerCase()){case"fade":s.elements.tooltip.fadeOut(s.options.hide.effect.length,w);break;case"slide":s.elements.tooltip.slideUp(s.options.hide.effect.length,w);break;case"grow":s.elements.tooltip.hide(s.options.hide.effect.length,w);break;default:s.elements.tooltip.hide(null,w);break}s.elements.tooltip.removeClass(s.options.style.classes.active)}return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_HIDDEN,"hide")},updatePosition:function(w,x){var C,G,L,J,H,E,y,I,B,D,K,A,F,z;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updatePosition")}else{if(s.options.position.type=="static"){return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.CANNOT_POSITION_STATIC,"updatePosition")}}G={position:{left:0,top:0},dimensions:{height:0,width:0},corner:s.options.position.corner.target};L={position:s.getPosition(),dimensions:s.getDimensions(),corner:s.options.position.corner.tooltip};if(s.options.position.target!=="mouse"){if(s.options.position.target.get(0).nodeName.toLowerCase()=="area"){J=s.options.position.target.attr("coords").split(",");for(C=0;C<J.length;C++){J[C]=parseInt(J[C])}H=s.options.position.target.parent("map").attr("name");E=f('img[usemap="#'+H+'"]:first').offset();G.position={left:Math.floor(E.left+J[0]),top:Math.floor(E.top+J[1])};switch(s.options.position.target.attr("shape").toLowerCase()){case"rect":G.dimensions={width:Math.ceil(Math.abs(J[2]-J[0])),height:Math.ceil(Math.abs(J[3]-J[1]))};break;case"circle":G.dimensions={width:J[2]+1,height:J[2]+1};break;case"poly":G.dimensions={width:J[0],height:J[1]};for(C=0;C<J.length;C++){if(C%2==0){if(J[C]>G.dimensions.width){G.dimensions.width=J[C]}if(J[C]<J[0]){G.position.left=Math.floor(E.left+J[C])}}else{if(J[C]>G.dimensions.height){G.dimensions.height=J[C]}if(J[C]<J[1]){G.position.top=Math.floor(E.top+J[C])}}}G.dimensions.width=G.dimensions.width-(G.position.left-E.left);G.dimensions.height=G.dimensions.height-(G.position.top-E.top);break;default:return f.fn.qtip.log.error.call(s,4,f.fn.qtip.constants.INVALID_AREA_SHAPE,"updatePosition");break}G.dimensions.width-=2;G.dimensions.height-=2}else{if(s.options.position.target.add(document.body).length===1){G.position={left:f(document).scrollLeft(),top:f(document).scrollTop()};G.dimensions={height:f(window).height(),width:f(window).width()}}else{if(typeof s.options.position.target.attr("qtip")!=="undefined"){G.position=s.options.position.target.qtip("api").cache.position}else{G.position=s.options.position.target.offset()}G.dimensions={height:s.options.position.target.outerHeight(),width:s.options.position.target.outerWidth()}}}y=f.extend({},G.position);if(G.corner.search(/right/i)!==-1){y.left+=G.dimensions.width}if(G.corner.search(/bottom/i)!==-1){y.top+=G.dimensions.height}if(G.corner.search(/((top|bottom)Middle)|center/)!==-1){y.left+=(G.dimensions.width/2)}if(G.corner.search(/((left|right)Middle)|center/)!==-1){y.top+=(G.dimensions.height/2)}}else{G.position=y={left:s.cache.mouse.x,top:s.cache.mouse.y};G.dimensions={height:1,width:1}}if(L.corner.search(/right/i)!==-1){y.left-=L.dimensions.width}if(L.corner.search(/bottom/i)!==-1){y.top-=L.dimensions.height}if(L.corner.search(/((top|bottom)Middle)|center/)!==-1){y.left-=(L.dimensions.width/2)}if(L.corner.search(/((left|right)Middle)|center/)!==-1){y.top-=(L.dimensions.height/2)}I=(f.browser.msie)?1:0;B=(f.browser.msie&&parseInt(f.browser.version.charAt(0))===6)?1:0;if(s.options.style.border.radius>0){if(L.corner.search(/Left/)!==-1){y.left-=s.options.style.border.radius}else{if(L.corner.search(/Right/)!==-1){y.left+=s.options.style.border.radius}}if(L.corner.search(/Top/)!==-1){y.top-=s.options.style.border.radius}else{if(L.corner.search(/Bottom/)!==-1){y.top+=s.options.style.border.radius}}}if(I){if(L.corner.search(/top/)!==-1){y.top-=I}else{if(L.corner.search(/bottom/)!==-1){y.top+=I}}if(L.corner.search(/left/)!==-1){y.left-=I}else{if(L.corner.search(/right/)!==-1){y.left+=I}}if(L.corner.search(/leftMiddle|rightMiddle/)!==-1){y.top-=1}}if(s.options.position.adjust.screen===true){y=o.call(s,y,G,L)}if(s.options.position.target==="mouse"&&s.options.position.adjust.mouse===true){if(s.options.position.adjust.screen===true&&s.elements.tip){K=s.elements.tip.attr("rel")}else{K=s.options.position.corner.tooltip}y.left+=(K.search(/right/i)!==-1)?-6:6;y.top+=(K.search(/bottom/i)!==-1)?-6:6}if(!s.elements.bgiframe&&f.browser.msie&&parseInt(f.browser.version.charAt(0))==6){f("select, object").each(function(){A=f(this).offset();A.bottom=A.top+f(this).height();A.right=A.left+f(this).width();if(y.top+L.dimensions.height>=A.top&&y.left+L.dimensions.width>=A.left){k.call(s)}})}y.left+=s.options.position.adjust.x;y.top+=s.options.position.adjust.y;F=s.getPosition();if(y.left!=F.left||y.top!=F.top){z=s.beforePositionUpdate.call(s,w);if(z===false){return s}s.cache.position=y;if(x===true){s.status.animated=true;s.elements.tooltip.animate(y,200,"swing",function(){s.status.animated=false})}else{s.elements.tooltip.css(y)}s.onPositionUpdate.call(s,w);if(typeof w!=="undefined"&&w.type&&w.type!=="mousemove"){f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_POSITION_UPDATED,"updatePosition")}}return s},updateWidth:function(w){var x;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updateWidth")}else{if(w&&typeof w!=="number"){return f.fn.qtip.log.error.call(s,2,"newWidth must be of type number","updateWidth")}}x=s.elements.contentWrapper.siblings().add(s.elements.tip).add(s.elements.button);if(!w){if(typeof s.options.style.width.value=="number"){w=s.options.style.width.value}else{s.elements.tooltip.css({width:"auto"});x.hide();if(f.browser.msie){s.elements.wrapper.add(s.elements.contentWrapper.children()).css({zoom:"normal"})}w=s.getDimensions().width+1;if(!s.options.style.width.value){if(w>s.options.style.width.max){w=s.options.style.width.max}if(w<s.options.style.width.min){w=s.options.style.width.min}}}}if(w%2!==0){w-=1}s.elements.tooltip.width(w);x.show();if(s.options.style.border.radius){s.elements.tooltip.find(".qtip-betweenCorners").each(function(y){f(this).width(w-(s.options.style.border.radius*2))})}if(f.browser.msie){s.elements.wrapper.add(s.elements.contentWrapper.children()).css({zoom:"1"});s.elements.wrapper.width(w);if(s.elements.bgiframe){s.elements.bgiframe.width(w).height(s.getDimensions.height)}}return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_WIDTH_UPDATED,"updateWidth")},updateStyle:function(w){var z,A,x,y,B;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updateStyle")}else{if(typeof w!=="string"||!f.fn.qtip.styles[w]){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.STYLE_NOT_DEFINED,"updateStyle")}}s.options.style=a.call(s,f.fn.qtip.styles[w],s.options.user.style);s.elements.content.css(q(s.options.style));if(s.options.content.title.text!==false){s.elements.title.css(q(s.options.style.title,true))}s.elements.contentWrapper.css({borderColor:s.options.style.border.color});if(s.options.style.tip.corner!==false){if(f("<canvas>").get(0).getContext){z=s.elements.tooltip.find(".qtip-tip canvas:first");x=z.get(0).getContext("2d");x.clearRect(0,0,300,300);y=z.parent("div[rel]:first").attr("rel");B=b(y,s.options.style.tip.size.width,s.options.style.tip.size.height);h.call(s,z,B,s.options.style.tip.color||s.options.style.border.color)}else{if(f.browser.msie){z=s.elements.tooltip.find('.qtip-tip [nodeName="shape"]');z.attr("fillcolor",s.options.style.tip.color||s.options.style.border.color)}}}if(s.options.style.border.radius>0){s.elements.tooltip.find(".qtip-betweenCorners").css({backgroundColor:s.options.style.border.color});if(f("<canvas>").get(0).getContext){A=g(s.options.style.border.radius);s.elements.tooltip.find(".qtip-wrapper canvas").each(function(){x=f(this).get(0).getContext("2d");x.clearRect(0,0,300,300);y=f(this).parent("div[rel]:first").attr("rel");r.call(s,f(this),A[y],s.options.style.border.radius,s.options.style.border.color)})}else{if(f.browser.msie){s.elements.tooltip.find('.qtip-wrapper [nodeName="arc"]').each(function(){f(this).attr("fillcolor",s.options.style.border.color)})}}}return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_STYLE_UPDATED,"updateStyle")},updateContent:function(A,y){var z,x,w;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updateContent")}else{if(!A){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.NO_CONTENT_PROVIDED,"updateContent")}}z=s.beforeContentUpdate.call(s,A);if(typeof z=="string"){A=z}else{if(z===false){return}}if(f.browser.msie){s.elements.contentWrapper.children().css({zoom:"normal"})}if(A.jquery&&A.length>0){A.clone(true).appendTo(s.elements.content).show()}else{s.elements.content.html(A)}x=s.elements.content.find("img[complete=false]");if(x.length>0){w=0;x.each(function(C){f('<img src="'+f(this).attr("src")+'" />').load(function(){if(++w==x.length){B()}})})}else{B()}function B(){s.updateWidth();if(y!==false){if(s.options.position.type!=="static"){s.updatePosition(s.elements.tooltip.is(":visible"),true)}if(s.options.style.tip.corner!==false){n.call(s)}}}s.onContentUpdate.call(s);return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_CONTENT_UPDATED,"loadContent")},loadContent:function(w,z,A){var y;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"loadContent")}y=s.beforeContentLoad.call(s);if(y===false){return s}if(A=="post"){f.post(w,z,x)}else{f.get(w,z,x)}function x(B){s.onContentLoad.call(s);f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_CONTENT_LOADED,"loadContent");s.updateContent(B)}return s},updateTitle:function(w){if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updateTitle")}else{if(!w){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.NO_CONTENT_PROVIDED,"updateTitle")}}returned=s.beforeTitleUpdate.call(s);if(returned===false){return s}if(s.elements.button){s.elements.button=s.elements.button.clone(true)}s.elements.title.html(w);if(s.elements.button){s.elements.title.prepend(s.elements.button)}s.onTitleUpdate.call(s);return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_TITLE_UPDATED,"updateTitle")},focus:function(A){var y,x,w,z;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"focus")}else{if(s.options.position.type=="static"){return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.CANNOT_FOCUS_STATIC,"focus")}}y=parseInt(s.elements.tooltip.css("z-index"));x=6000+f("div.qtip[qtip]").length-1;if(!s.status.focused&&y!==x){z=s.beforeFocus.call(s,A);if(z===false){return s}f("div.qtip[qtip]").not(s.elements.tooltip).each(function(){if(f(this).qtip("api").status.rendered===true){w=parseInt(f(this).css("z-index"));if(typeof w=="number"&&w>-1){f(this).css({zIndex:parseInt(f(this).css("z-index"))-1})}f(this).qtip("api").status.focused=false}});s.elements.tooltip.css({zIndex:x});s.status.focused=true;s.onFocus.call(s,A);f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_FOCUSED,"focus")}return s},disable:function(w){if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"disable")}if(w){if(!s.status.disabled){s.status.disabled=true;f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_DISABLED,"disable")}else{f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.TOOLTIP_ALREADY_DISABLED,"disable")}}else{if(s.status.disabled){s.status.disabled=false;f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_ENABLED,"disable")}else{f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.TOOLTIP_ALREADY_ENABLED,"disable")}}return s},destroy:function(){var w,x,y;x=s.beforeDestroy.call(s);if(x===false){return s}if(s.status.rendered){s.options.show.when.target.unbind("mousemove.qtip",s.updatePosition);s.options.show.when.target.unbind("mouseout.qtip",s.hide);s.options.show.when.target.unbind(s.options.show.when.event+".qtip");s.options.hide.when.target.unbind(s.options.hide.when.event+".qtip");s.elements.tooltip.unbind(s.options.hide.when.event+".qtip");s.elements.tooltip.unbind("mouseover.qtip",s.focus);s.elements.tooltip.remove()}else{s.options.show.when.target.unbind(s.options.show.when.event+".qtip-create")}if(typeof s.elements.target.data("qtip")=="object"){y=s.elements.target.data("qtip").interfaces;if(typeof y=="object"&&y.length>0){for(w=0;w<y.length-1;w++){if(y[w].id==s.id){y.splice(w,1)}}}}delete f.fn.qtip.interfaces[s.id];if(typeof y=="object"&&y.length>0){s.elements.target.data("qtip").current=y.length-1}else{s.elements.target.removeData("qtip")}s.onDestroy.call(s);f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_DESTROYED,"destroy");return s.elements.target},getPosition:function(){var w,x;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"getPosition")}w=(s.elements.tooltip.css("display")!=="none")?false:true;if(w){s.elements.tooltip.css({visiblity:"hidden"}).show()}x=s.elements.tooltip.offset();if(w){s.elements.tooltip.css({visiblity:"visible"}).hide()}return x},getDimensions:function(){var w,x;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"getDimensions")}w=(!s.elements.tooltip.is(":visible"))?true:false;if(w){s.elements.tooltip.css({visiblity:"hidden"}).show()}x={height:s.elements.tooltip.outerHeight(),width:s.elements.tooltip.outerWidth()};if(w){s.elements.tooltip.css({visiblity:"visible"}).hide()}return x}})}function p(){var s,w,u,t,v,y,x;s=this;s.beforeRender.call(s);s.status.rendered=true;s.elements.tooltip='<div qtip="'+s.id+'" class="qtip '+(s.options.style.classes.tooltip||s.options.style)+'"style="display:none; -moz-border-radius:0; -webkit-border-radius:0; border-radius:0;position:'+s.options.position.type+';">  <div class="qtip-wrapper" style="position:relative; overflow:hidden; text-align:left;">    <div class="qtip-contentWrapper" style="overflow:hidden;">       <div class="qtip-content '+s.options.style.classes.content+'"></div></div></div></div>';s.elements.tooltip=f(s.elements.tooltip);s.elements.tooltip.appendTo(s.options.position.container);s.elements.tooltip.data("qtip",{current:0,interfaces:[s]});s.elements.wrapper=s.elements.tooltip.children("div:first");s.elements.contentWrapper=s.elements.wrapper.children("div:first").css({background:s.options.style.background});s.elements.content=s.elements.contentWrapper.children("div:first").css(q(s.options.style));if(f.browser.msie){s.elements.wrapper.add(s.elements.content).css({zoom:1})}if(s.options.hide.when.event=="unfocus"){s.elements.tooltip.attr("unfocus",true)}if(typeof s.options.style.width.value=="number"){s.updateWidth()}if(f("<canvas>").get(0).getContext||f.browser.msie){if(s.options.style.border.radius>0){m.call(s)}else{s.elements.contentWrapper.css({border:s.options.style.border.width+"px solid "+s.options.style.border.color})}if(s.options.style.tip.corner!==false){e.call(s)}}else{s.elements.contentWrapper.css({border:s.options.style.border.width+"px solid "+s.options.style.border.color});s.options.style.border.radius=0;s.options.style.tip.corner=false;f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.CANVAS_VML_NOT_SUPPORTED,"render")}if((typeof s.options.content.text=="string"&&s.options.content.text.length>0)||(s.options.content.text.jquery&&s.options.content.text.length>0)){u=s.options.content.text}else{if(typeof s.elements.target.attr("title")=="string"&&s.elements.target.attr("title").length>0){u=s.elements.target.attr("title").replace("\\n","<br />");s.elements.target.attr("title","")}else{if(typeof s.elements.target.attr("alt")=="string"&&s.elements.target.attr("alt").length>0){u=s.elements.target.attr("alt").replace("\\n","<br />");s.elements.target.attr("alt","")}else{u=" ";f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.NO_VALID_CONTENT,"render")}}}if(s.options.content.title.text!==false){j.call(s)}s.updateContent(u);l.call(s);if(s.options.show.ready===true){s.show()}if(s.options.content.url!==false){t=s.options.content.url;v=s.options.content.data;y=s.options.content.method||"get";s.loadContent(t,v,y)}s.onRender.call(s);f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_RENDERED,"render")}function m(){var F,z,t,B,x,E,u,G,D,y,w,C,A,s,v;F=this;F.elements.wrapper.find(".qtip-borderBottom, .qtip-borderTop").remove();t=F.options.style.border.width;B=F.options.style.border.radius;x=F.options.style.border.color||F.options.style.tip.color;E=g(B);u={};for(z in E){u[z]='<div rel="'+z+'" style="'+((z.search(/Left/)!==-1)?"left":"right")+":0; position:absolute; height:"+B+"px; width:"+B+'px; overflow:hidden; line-height:0.1px; font-size:1px">';if(f("<canvas>").get(0).getContext){u[z]+='<canvas height="'+B+'" width="'+B+'" style="vertical-align: top"></canvas>'}else{if(f.browser.msie){G=B*2+3;u[z]+='<v:arc stroked="false" fillcolor="'+x+'" startangle="'+E[z][0]+'" endangle="'+E[z][1]+'" style="width:'+G+"px; height:"+G+"px; margin-top:"+((z.search(/bottom/)!==-1)?-2:-1)+"px; margin-left:"+((z.search(/Right/)!==-1)?E[z][2]-3.5:-1)+'px; vertical-align:top; display:inline-block; behavior:url(#default#VML)"></v:arc>'}}u[z]+="</div>"}D=F.getDimensions().width-(Math.max(t,B)*2);y='<div class="qtip-betweenCorners" style="height:'+B+"px; width:"+D+"px; overflow:hidden; background-color:"+x+'; line-height:0.1px; font-size:1px;">';w='<div class="qtip-borderTop" dir="ltr" style="height:'+B+"px; margin-left:"+B+'px; line-height:0.1px; font-size:1px; padding:0;">'+u.topLeft+u.topRight+y;F.elements.wrapper.prepend(w);C='<div class="qtip-borderBottom" dir="ltr" style="height:'+B+"px; margin-left:"+B+'px; line-height:0.1px; font-size:1px; padding:0;">'+u.bottomLeft+u.bottomRight+y;F.elements.wrapper.append(C);if(f("<canvas>").get(0).getContext){F.elements.wrapper.find("canvas").each(function(){A=E[f(this).parent("[rel]:first").attr("rel")];r.call(F,f(this),A,B,x)})}else{if(f.browser.msie){F.elements.tooltip.append('<v:image style="behavior:url(#default#VML);"></v:image>')}}s=Math.max(B,(B+(t-B)));v=Math.max(t-B,0);F.elements.contentWrapper.css({border:"0px solid "+x,borderWidth:v+"px "+s+"px"})}function r(u,w,s,t){var v=u.get(0).getContext("2d");v.fillStyle=t;v.beginPath();v.arc(w[0],w[1],s,0,Math.PI*2,false);v.fill()}function e(v){var t,s,x,u,w;t=this;if(t.elements.tip!==null){t.elements.tip.remove()}s=t.options.style.tip.color||t.options.style.border.color;if(t.options.style.tip.corner===false){return}else{if(!v){v=t.options.style.tip.corner}}x=b(v,t.options.style.tip.size.width,t.options.style.tip.size.height);t.elements.tip='<div class="'+t.options.style.classes.tip+'" dir="ltr" rel="'+v+'" style="position:absolute; height:'+t.options.style.tip.size.height+"px; width:"+t.options.style.tip.size.width+'px; margin:0 auto; line-height:0.1px; font-size:1px;">';if(f("<canvas>").get(0).getContext){t.elements.tip+='<canvas height="'+t.options.style.tip.size.height+'" width="'+t.options.style.tip.size.width+'"></canvas>'}else{if(f.browser.msie){u=t.options.style.tip.size.width+","+t.options.style.tip.size.height;w="m"+x[0][0]+","+x[0][1];w+=" l"+x[1][0]+","+x[1][1];w+=" "+x[2][0]+","+x[2][1];w+=" xe";t.elements.tip+='<v:shape fillcolor="'+s+'" stroked="false" filled="true" path="'+w+'" coordsize="'+u+'" style="width:'+t.options.style.tip.size.width+"px; height:"+t.options.style.tip.size.height+"px; line-height:0.1px; display:inline-block; behavior:url(#default#VML); vertical-align:"+((v.search(/top/)!==-1)?"bottom":"top")+'"></v:shape>';t.elements.tip+='<v:image style="behavior:url(#default#VML);"></v:image>';t.elements.contentWrapper.css("position","relative")}}t.elements.tooltip.prepend(t.elements.tip+"</div>");t.elements.tip=t.elements.tooltip.find("."+t.options.style.classes.tip).eq(0);if(f("<canvas>").get(0).getContext){h.call(t,t.elements.tip.find("canvas:first"),x,s)}if(v.search(/top/)!==-1&&f.browser.msie&&parseInt(f.browser.version.charAt(0))===6){t.elements.tip.css({marginTop:-4})}n.call(t,v)}function h(t,v,s){var u=t.get(0).getContext("2d");u.fillStyle=s;u.beginPath();u.moveTo(v[0][0],v[0][1]);u.lineTo(v[1][0],v[1][1]);u.lineTo(v[2][0],v[2][1]);u.fill()}function n(u){var t,w,s,x,v;t=this;if(t.options.style.tip.corner===false||!t.elements.tip){return}if(!u){u=t.elements.tip.attr("rel")}w=positionAdjust=(f.browser.msie)?1:0;t.elements.tip.css(u.match(/left|right|top|bottom/)[0],0);if(u.search(/top|bottom/)!==-1){if(f.browser.msie){if(parseInt(f.browser.version.charAt(0))===6){positionAdjust=(u.search(/top/)!==-1)?-3:1}else{positionAdjust=(u.search(/top/)!==-1)?1:2}}if(u.search(/Middle/)!==-1){t.elements.tip.css({left:"50%",marginLeft:-(t.options.style.tip.size.width/2)})}else{if(u.search(/Left/)!==-1){t.elements.tip.css({left:t.options.style.border.radius-w})}else{if(u.search(/Right/)!==-1){t.elements.tip.css({right:t.options.style.border.radius+w})}}}if(u.search(/top/)!==-1){t.elements.tip.css({top:-positionAdjust})}else{t.elements.tip.css({bottom:positionAdjust})}}else{if(u.search(/left|right/)!==-1){if(f.browser.msie){positionAdjust=(parseInt(f.browser.version.charAt(0))===6)?1:((u.search(/left/)!==-1)?1:2)}if(u.search(/Middle/)!==-1){t.elements.tip.css({top:"50%",marginTop:-(t.options.style.tip.size.height/2)})}else{if(u.search(/Top/)!==-1){t.elements.tip.css({top:t.options.style.border.radius-w})}else{if(u.search(/Bottom/)!==-1){t.elements.tip.css({bottom:t.options.style.border.radius+w})}}}if(u.search(/left/)!==-1){t.elements.tip.css({left:-positionAdjust})}else{t.elements.tip.css({right:positionAdjust})}}}s="padding-"+u.match(/left|right|top|bottom/)[0];x=t.options.style.tip.size[(s.search(/left|right/)!==-1)?"width":"height"];t.elements.tooltip.css("padding",0);t.elements.tooltip.css(s,x);if(f.browser.msie&&parseInt(f.browser.version.charAt(0))==6){v=parseInt(t.elements.tip.css("margin-top"))||0;v+=parseInt(t.elements.content.css("margin-top"))||0;t.elements.tip.css({marginTop:v})}}function j(){var s=this;if(s.elements.title!==null){s.elements.title.remove()}s.elements.title=f('<div class="'+s.options.style.classes.title+'">').css(q(s.options.style.title,true)).css({zoom:(f.browser.msie)?1:0}).prependTo(s.elements.contentWrapper);if(s.options.content.title.text){s.updateTitle.call(s,s.options.content.title.text)}if(s.options.content.title.button!==false&&typeof s.options.content.title.button=="string"){s.elements.button=f('<a class="'+s.options.style.classes.button+'" style="float:right; position: relative"></a>').css(q(s.options.style.button,true)).html(s.options.content.title.button).prependTo(s.elements.title).click(function(t){if(!s.status.disabled){s.hide(t)}})}}function l(){var t,v,u,s;t=this;v=t.options.show.when.target;u=t.options.hide.when.target;if(t.options.hide.fixed){u=u.add(t.elements.tooltip)}if(t.options.hide.when.event=="inactive"){s=["click","dblclick","mousedown","mouseup","mousemove","mouseout","mouseenter","mouseleave","mouseover"];function y(z){if(t.status.disabled===true){return}clearTimeout(t.timers.inactive);t.timers.inactive=setTimeout(function(){f(s).each(function(){u.unbind(this+".qtip-inactive");t.elements.content.unbind(this+".qtip-inactive")});t.hide(z)},t.options.hide.delay)}}else{if(t.options.hide.fixed===true){t.elements.tooltip.bind("mouseover.qtip",function(){if(t.status.disabled===true){return}clearTimeout(t.timers.hide)})}}function x(z){if(t.status.disabled===true){return}if(t.options.hide.when.event=="inactive"){f(s).each(function(){u.bind(this+".qtip-inactive",y);t.elements.content.bind(this+".qtip-inactive",y)});y()}clearTimeout(t.timers.show);clearTimeout(t.timers.hide);t.timers.show=setTimeout(function(){t.show(z)},t.options.show.delay)}function w(z){if(t.status.disabled===true){return}if(t.options.hide.fixed===true&&t.options.hide.when.event.search(/mouse(out|leave)/i)!==-1&&f(z.relatedTarget).parents("div.qtip[qtip]").length>0){z.stopPropagation();z.preventDefault();clearTimeout(t.timers.hide);return false}clearTimeout(t.timers.show);clearTimeout(t.timers.hide);t.elements.tooltip.stop(true,true);t.timers.hide=setTimeout(function(){t.hide(z)},t.options.hide.delay)}if((t.options.show.when.target.add(t.options.hide.when.target).length===1&&t.options.show.when.event==t.options.hide.when.event&&t.options.hide.when.event!=="inactive")||t.options.hide.when.event=="unfocus"){t.cache.toggle=0;v.bind(t.options.show.when.event+".qtip",function(z){if(t.cache.toggle==0){x(z)}else{w(z)}})}else{v.bind(t.options.show.when.event+".qtip",x);if(t.options.hide.when.event!=="inactive"){u.bind(t.options.hide.when.event+".qtip",w)}}if(t.options.position.type.search(/(fixed|absolute)/)!==-1){t.elements.tooltip.bind("mouseover.qtip",t.focus)}if(t.options.position.target==="mouse"&&t.options.position.type!=="static"){v.bind("mousemove.qtip",function(z){t.cache.mouse={x:z.pageX,y:z.pageY};if(t.status.disabled===false&&t.options.position.adjust.mouse===true&&t.options.position.type!=="static"&&t.elements.tooltip.css("display")!=="none"){t.updatePosition(z)}})}}function o(u,v,A){var z,s,x,y,t,w;z=this;if(A.corner=="center"){return v.position}s=f.extend({},u);y={x:false,y:false};t={left:(s.left<f.fn.qtip.cache.screen.scroll.left),right:(s.left+A.dimensions.width+2>=f.fn.qtip.cache.screen.width+f.fn.qtip.cache.screen.scroll.left),top:(s.top<f.fn.qtip.cache.screen.scroll.top),bottom:(s.top+A.dimensions.height+2>=f.fn.qtip.cache.screen.height+f.fn.qtip.cache.screen.scroll.top)};x={left:(t.left&&(A.corner.search(/right/i)!=-1||(A.corner.search(/right/i)==-1&&!t.right))),right:(t.right&&(A.corner.search(/left/i)!=-1||(A.corner.search(/left/i)==-1&&!t.left))),top:(t.top&&A.corner.search(/top/i)==-1),bottom:(t.bottom&&A.corner.search(/bottom/i)==-1)};if(x.left){if(z.options.position.target!=="mouse"){s.left=v.position.left+v.dimensions.width}else{s.left=z.cache.mouse.x}y.x="Left"}else{if(x.right){if(z.options.position.target!=="mouse"){s.left=v.position.left-A.dimensions.width}else{s.left=z.cache.mouse.x-A.dimensions.width}y.x="Right"}}if(x.top){if(z.options.position.target!=="mouse"){s.top=v.position.top+v.dimensions.height}else{s.top=z.cache.mouse.y}y.y="top"}else{if(x.bottom){if(z.options.position.target!=="mouse"){s.top=v.position.top-A.dimensions.height}else{s.top=z.cache.mouse.y-A.dimensions.height}y.y="bottom"}}if(s.left<0){s.left=u.left;y.x=false}if(s.top<0){s.top=u.top;y.y=false}if(z.options.style.tip.corner!==false){s.corner=new String(A.corner);if(y.x!==false){s.corner=s.corner.replace(/Left|Right|Middle/,y.x)}if(y.y!==false){s.corner=s.corner.replace(/top|bottom/,y.y)}if(s.corner!==z.elements.tip.attr("rel")){e.call(z,s.corner)}}return s}function q(u,t){var v,s;v=f.extend(true,{},u);for(s in v){if(t===true&&s.search(/(tip|classes)/i)!==-1){delete v[s]}else{if(!t&&s.search(/(width|border|tip|title|classes|user)/i)!==-1){delete v[s]}}}return v}function c(s){if(typeof s.tip!=="object"){s.tip={corner:s.tip}}if(typeof s.tip.size!=="object"){s.tip.size={width:s.tip.size,height:s.tip.size}}if(typeof s.border!=="object"){s.border={width:s.border}}if(typeof s.width!=="object"){s.width={value:s.width}}if(typeof s.width.max=="string"){s.width.max=parseInt(s.width.max.replace(/([0-9]+)/i,"$1"))}if(typeof s.width.min=="string"){s.width.min=parseInt(s.width.min.replace(/([0-9]+)/i,"$1"))}if(typeof s.tip.size.x=="number"){s.tip.size.width=s.tip.size.x;delete s.tip.size.x}if(typeof s.tip.size.y=="number"){s.tip.size.height=s.tip.size.y;delete s.tip.size.y}return s}function a(){var s,t,u,x,v,w;s=this;u=[true,{}];for(t=0;t<arguments.length;t++){u.push(arguments[t])}x=[f.extend.apply(f,u)];while(typeof x[0].name=="string"){x.unshift(c(f.fn.qtip.styles[x[0].name]))}x.unshift(true,{classes:{tooltip:"qtip-"+(arguments[0].name||"defaults")}},f.fn.qtip.styles.defaults);v=f.extend.apply(f,x);w=(f.browser.msie)?1:0;v.tip.size.width+=w;v.tip.size.height+=w;if(v.tip.size.width%2>0){v.tip.size.width+=1}if(v.tip.size.height%2>0){v.tip.size.height+=1}if(v.tip.corner===true){v.tip.corner=(s.options.position.corner.tooltip==="center")?false:s.options.position.corner.tooltip}return v}function b(v,u,t){var s={bottomRight:[[0,0],[u,t],[u,0]],bottomLeft:[[0,0],[u,0],[0,t]],topRight:[[0,t],[u,0],[u,t]],topLeft:[[0,0],[0,t],[u,t]],topMiddle:[[0,t],[u/2,0],[u,t]],bottomMiddle:[[0,0],[u,0],[u/2,t]],rightMiddle:[[0,0],[u,t/2],[0,t]],leftMiddle:[[u,0],[u,t],[0,t/2]]};s.leftTop=s.bottomRight;s.rightTop=s.bottomLeft;s.leftBottom=s.topRight;s.rightBottom=s.topLeft;return s[v]}function g(s){var t;if(f("<canvas>").get(0).getContext){t={topLeft:[s,s],topRight:[0,s],bottomLeft:[s,0],bottomRight:[0,0]}}else{if(f.browser.msie){t={topLeft:[-90,90,0],topRight:[-90,90,-s],bottomLeft:[90,270,0],bottomRight:[90,270,-s]}}}return t}function k(){var s,t,u;s=this;u=s.getDimensions();t='<iframe class="qtip-bgiframe" frameborder="0" tabindex="-1" src="javascript:false" style="display:block; position:absolute; z-index:-1; filter:alpha(opacity=\'0\'); border: 1px solid red; height:'+u.height+"px; width:"+u.width+'px" />';s.elements.bgiframe=s.elements.wrapper.prepend(t).children(".qtip-bgiframe:first")}f(document).ready(function(){f.fn.qtip.cache={screen:{scroll:{left:f(window).scrollLeft(),top:f(window).scrollTop()},width:f(window).width(),height:f(window).height()}};var s;f(window).bind("resize scroll",function(t){clearTimeout(s);s=setTimeout(function(){if(t.type==="scroll"){f.fn.qtip.cache.screen.scroll={left:f(window).scrollLeft(),top:f(window).scrollTop()}}else{f.fn.qtip.cache.screen.width=f(window).width();f.fn.qtip.cache.screen.height=f(window).height()}for(i=0;i<f.fn.qtip.interfaces.length;i++){var u=f.fn.qtip.interfaces[i];if(u.status.rendered===true&&(u.options.position.type!=="static"||u.options.position.adjust.scroll&&t.type==="scroll"||u.options.position.adjust.resize&&t.type==="resize")){u.updatePosition(t,true)}}},100)});f(document).bind("mousedown.qtip",function(t){if(f(t.target).parents("div.qtip").length===0){f(".qtip[unfocus]").each(function(){var u=f(this).qtip("api");if(f(this).is(":visible")&&!u.status.disabled&&f(t.target).add(u.elements.target).length>1){u.hide(t)}})}})});f.fn.qtip.interfaces=[];f.fn.qtip.log={error:function(){return this}};f.fn.qtip.constants={};f.fn.qtip.defaults={content:{prerender:false,text:false,url:false,data:null,title:{text:false,button:false}},position:{target:false,corner:{target:"bottomRight",tooltip:"topLeft"},adjust:{x:0,y:0,mouse:true,screen:false,scroll:true,resize:true},type:"absolute",container:false},show:{when:{target:false,event:"mouseover"},effect:{type:"fade",length:100},delay:140,solo:false,ready:false},hide:{when:{target:false,event:"mouseout"},effect:{type:"fade",length:100},delay:0,fixed:false},api:{beforeRender:function(){},onRender:function(){},beforePositionUpdate:function(){},onPositionUpdate:function(){},beforeShow:function(){},onShow:function(){},beforeHide:function(){},onHide:function(){},beforeContentUpdate:function(){},onContentUpdate:function(){},beforeContentLoad:function(){},onContentLoad:function(){},beforeTitleUpdate:function(){},onTitleUpdate:function(){},beforeDestroy:function(){},onDestroy:function(){},beforeFocus:function(){},onFocus:function(){}}};f.fn.qtip.styles={defaults:{background:"white",color:"#111",overflow:"hidden",textAlign:"left",width:{min:0,max:250},padding:"5px 9px",border:{width:1,radius:0,color:"#d3d3d3"},tip:{corner:false,color:false,size:{width:13,height:13},opacity:1},title:{background:"#e1e1e1",fontWeight:"bold",padding:"7px 12px"},button:{cursor:"pointer"},classes:{target:"",tip:"qtip-tip",title:"qtip-title",button:"qtip-button",content:"qtip-content",active:"qtip-active"}},cream:{border:{width:3,radius:0,color:"#F9E98E"},title:{background:"#F0DE7D",color:"#A27D35"},background:"#FBF7AA",color:"#A27D35",classes:{tooltip:"qtip-cream"}},light:{border:{width:3,radius:0,color:"#E2E2E2"},title:{background:"#f1f1f1",color:"#454545"},background:"white",color:"#454545",classes:{tooltip:"qtip-light"}},dark:{border:{width:3,radius:0,color:"#303030"},title:{background:"#404040",color:"#f3f3f3"},background:"#505050",color:"#f3f3f3",classes:{tooltip:"qtip-dark"}},red:{border:{width:3,radius:0,color:"#CE6F6F"},title:{background:"#f28279",color:"#9C2F2F"},background:"#F79992",color:"#9C2F2F",classes:{tooltip:"qtip-red"}},green:{border:{width:3,radius:0,color:"#A9DB66"},title:{background:"#b9db8c",color:"#58792E"},background:"#CDE6AC",color:"#58792E",classes:{tooltip:"qtip-green"}},blue:{border:{width:3,radius:0,color:"#ADD9ED"},title:{background:"#D0E9F5",color:"#5E99BD"},background:"#E5F6FE",color:"#4D9FBF",classes:{tooltip:"qtip-blue"}}}})(jQuery);

var media_query = 'desktop';
var large_desktop = false;
var swipeLeft = rlLangDir == 'rtl' ? 'swiperight' : 'swipeleft';
var swipeRight = rlLangDir == 'rtl' ? 'swipeleft' : 'swiperight';
var fl_ratio = typeof window.devicePixelRatio != undefined ? window.devicePixelRatio : 1;

/**
* document ready
*
**/
$(document).ready(function(){
    flynaxTpl.customInput();
    flynaxTpl.langSelector();
    flynaxTpl.userNavbar();
    flynaxTpl.keywordSearch();
    flynaxTpl.urlHash();
    flynaxTpl.tabsMore();

    $('div.sorting div.current').tplToggle({
        cont: $('div.sorting ul.fields'),
        parent: 'sorting'
    });

    $('section.side_block .expander').each(function(){
        $(this).parent().before($(this));
        $(this).tplToggle({
            cont: $(this).next(),
            id: 'cat_box_expander'
        });
    });

    if (['account_type', 'listing_details'].indexOf(rlPageInfo['controller']) >= 0) {
        flynaxTpl.contactSeller();
    }

    $('#refine_keyword_opt').click(function(){
        $(this).closest('form').find('.options').slideToggle();
    });

    var plans_controllers = ['add_listing', 'upgrade_listing', 'my_packages', 'add_banner', 'registration', 'profile'];

    /**
    * media queries handler
    *
    **/
    enquire.register("screen and (min-width: 992px)", {
        match: function(){
            media_query = 'desktop';

            flynaxTpl.stickMenu('match');
            flynaxTpl.menu();
            flynaxTpl.sidebar('clear');

            if ( !$('section.side_block > .expander').next().is(':visible') ){
                $('section.side_block > .expander').trigger('click');
            }
        },
        unmatch: function(){}
    }).register("screen and (min-width: 768px) and (max-width: 991px)", {
        match: function(){
            media_query = 'tablet';

            flynaxTpl.stickMenu('unmatch');
            $('section.main-menu').removeClass('fixed-menu');
            flynaxTpl.menu();

            flynaxTpl.sidebar();
        },
        unmatch: function(){}
    }).register("screen and (max-width: 767px)", {
        match: function(){
            media_query = 'mobile';

            flynaxTpl.stickMenu('unmatch');
            $('section.main-menu').removeClass('fixed-menu');
            $('section.main-menu ul.menu > li').attr('style', '');
            $('section.main-menu ul.menu').removeClass('menu').addClass('menu-mobile');
            flynaxTpl.mobileMenu();

            $('div.kw-search span.field').attr('style', '');
            $('div.kw-search').removeClass('kw-search kw-search-active').addClass('kw-search-mobile');
            $('#ac_interface').remove();

            flynaxTpl.sidebar();

            $('.grid_navbar .buttons .list').trigger('click');

            if ( $('section.side_block > .expander').next().is(':visible') ){
                $('section.side_block > .expander').trigger('click');
            }
        },
        unmatch: function(){
            $('section.main-menu ul.menu-mobile').removeClass('menu-mobile opened').addClass('menu');
            $('div.kw-search-mobile').removeClass('kw-search-mobile').addClass('kw-search');

            flynaxTpl.menu();
        },
        setup: function(){}
    });

    flFieldset();
    flynaxTpl.qtip();

    flynaxTpl.categoryTree();

    if ( rlPageInfo['key'] == 'home' ) {
        flynaxTpl.categoryAlphabet();
    }

    /* other */
    $('.numeric').numeric({
        decimal: rlConfig['price_separator']
    });
    $('footer .scroll-top').click(function(){
        $('body,html').animate({scrollTop: 0}, 'slow');
    });
});

/**
* template related javascript handlers
*
**/
var flynaxTplClass = function(){

    /**
    * reference to it self object
    *
    **/
    var self = this;

    var prevHash = '';

    /**
    * url hash handler
    *
    **/
    this.urlHash = function(){
        $(window).on('hashchange', function(e) {
            var hash = flynax.getHash();

            if (rlPageInfo['controller'] == 'listing_details'
                && (hash != 'map-fullscreen' && hash != '&ui-state=dialog')
            ) {
                var obj = 'ul.tabs li#tab_'+hash;
                tabsSwitcher(obj);
                if ( $(obj).length > 0 ) {
                    $('html, body').animate({scrollTop:$(obj).offset().top - 58});
                }
            }
            if ( prevHash == 'map-fullscreen' && !hash ) {
                $('#modal_block div.inner > div.close').trigger('click');
            }

            prevHash = flynax.getHash();
        });
    };

    /**
    * sidebar boxes arrangement handler
    *
    **/
    this.sidebar = function(mode){
        if ( rlPageInfo['controller'] == 'listing_details' ) {
            if ( mode == 'clear' ) {
                if ( $('#controller_area > aside.right').length <= 0 ) {
                    $('#controller_area').prepend($('div.listing-details > section.main-section').next());
                }
            }
            else {
                if ( $('div.listing-details > aside.right').length <= 0 ) {
                    $('div.listing-details > section.main-section').after($('#controller_area > aside.right'));
                }
            }

            if ( media_query == 'mobile' ) {
                $('div.top-navigation').prepend($('div.listing-details-icons'));

                $('.listing-details > .content-section').after('<aside class="left second-copy clearfix"></div>');
                $('.listing-details > aside.left.second-copy').append($('.listing-details > aside.right > section:not(.stick)'));
            }
            else {
                $('nav#content_nav_icons').prepend($('div.listing-details-icons'));

                $('.listing-details > aside.right').append($('.listing-details > aside.left.second-copy > *'));
                $('aside.left.second-copy').remove();
            }
        }
        else {
            if ( mode == 'clear' ) {
                if ( $('aside.left.second-copy').length > 0 ) {
                    $('#main_container > div > div > aside.left').append($('aside.left.first-copy > *'));
                    $('#main_container > div > div > aside.left').append($('aside.left.second-copy > *'));
                    $('aside.left.second-copy, aside.left.first-copy').remove();
                }
            }
            else {
                if ( $('aside.left.second-copy').length <= 0 ) {
                    $('#controller_area').before('<aside class="left first-copy clearfix"></div>');
                    $('#controller_area').after('<aside class="left second-copy clearfix"></div>');
                    $('aside.left.first-copy').append($('#main_container aside.left > *.stick'));
                    $('aside.left.second-copy').append($('#main_container aside.left > *:not(.stick)'));
                }
            }
        }
    };

    /**
    * custom tips
    *
    **/
    this.qtip = function(){
        var tmp_style = jQuery.extend({}, qtip_style);
        tmp_style.tip = 'bottomMiddle';

        $('.hint').each(function(){
            $(this).qtip({
                content: $(this).attr('title') ? $(this).attr('title') : $(this).prev('div.qtip_cont').html(),
                show: 'mouseover',
                hide: 'mouseout',
                position: {
                    corner: {
                        target: 'topMiddle',
                        tooltip: 'bottomMiddle'
                    }
                },
                style: tmp_style
            }).attr('title', '');
        });
    };

    /**
    * main menu handler
    *
    **/
    this.menu = function(){
        var menu = $('.main-menu ul.menu');

        $('ul.menu-mobile > li > a').unbind('click');

        /* clear */
        $('#main_menu_more li').remove();
        menu.find('li:not(:visible)').show();
        menu.find('li').width('auto');
        menu.find('li.more').removeClass('more_active');
        menu.find('li:not(.more)').attr('style', '');
        $('ul#main_menu_more').hide();

        /* build menu */
        var width = menu.width(),
            buttonWidth = menu.find('li.more').width() + 20,
            workWidth = width - buttonWidth,
            countWidth = 0,
            countItems = menu.find('li:not(:last)').length,
            border = false,
            margin = 0,
            effected = false;

        menu.find('li:not(:last)').each(function(index){
            countWidth += $(this).outerWidth() + margin;

            if ( index == 0 ) {
                countWidth -= margin/2;
            }
            index++;

            var rest = countItems != index ? 100 : 0;
            if ( workWidth - countWidth < rest ) {
                effected = true;

                if ( !border && countItems != index ) {
                    var newWidth = workWidth - (countWidth - $(this).width());
                    if ( newWidth < $(this).width() ) {
                        $(this).width(newWidth);
                    }
                    border = true;
                }
                else {
                    $('#main_menu_more').append('<li></li>');
                    $('#main_menu_more li:last').html($(this).find('a').parent().html()).addClass($(this).find('a').parent().attr('class'));
                    $(this).hide();
                }
            }
        });

        if ( effected ) {
            menu.find('li.more').show();
        }
        else {
            menu.find('li.more').hide();
        }

        /* set click handler */
        menu.find('li.more').unbind('click').click(function(){
            $(this).toggleClass('more_active');
            var width = menu.find('li.more > span').position(true).left;
            if ( rlLangDir == 'rtl' ) {
                width -= $('#main_menu_more').width();
            }
            $('#main_menu_more').css('left', width).toggle();
        });

        /* document click handler */
        $(document).bind('click touchstart', function(event){
            var close = true;
            $(event.target).parents().each(function(){
                if ( $(this).attr('id') == 'main_menu_more' || $(this).hasClass('more') || $(event.target).hasClass('more') ) {
                    close = false;
                }
            });

            if ( close ) {
                $('#main_menu_more').hide();
                menu.find('li.more').removeClass('more_active');
            }
        });
    };

    /**
    * mobile menu handler
    *
    **/
    this.mobileMenu = function(){
        $('div.mobile-menu-button').unbind('click').click(function(){
            if ( $('ul.menu-mobile').hasClass('opened') ) {
                $('ul.menu-mobile').removeClass('opened');
            }
            else {
                $('ul.menu-mobile').addClass('opened');
                flynax.slideTo('.menu-mobile.opened');
            }
        });

        var mobileMenuClose = function(event){
            if ( !$(event.target).hasClass('mobile-menu-button') && !$(event.target).parents().hasClass('menu-mobile') && !$(event.target).hasClass('menu-mobile') ) {
                $('ul.menu-mobile').removeClass('opened');
            }
        };

        $(document).unbind('click touchend', mobileMenuClose).bind('click touchend', mobileMenuClose);
    };

    /**
    * stick menu handler
    *
    **/
    this.stickMenu = function(mode){
        if ( mode == 'match' ) {
            var $header = $('body > header');
            var mainMenuAction = function(){
                var $target = $header.find('> section.point1');
                var topOffset = $target.offset().top + $target.height();

                if ($(document).scrollTop() > topOffset) {
                    $header.addClass('fixed-menu');
                } else {
                    $header.removeClass('fixed-menu');
                }
            };
            $(document).bind('touchmove scroll', mainMenuAction);
        }
        else {
            $(document).unbind('touchmove scroll', mainMenuAction);
        }
    };

    /**
    * highlight search results in grid
    **/
    this.highlightResults = function(query, details){
        if ( !query )
            return;

        query = trim(query);
        var repeat = new RegExp('(\\s)\\1+', 'gm');
        query = query.replace(repeat, ' ');
        query = query.split(' ');

        var pattern = '';
        for (var i = 0; i < query.length; i++) {
            if (query[i].length > 2) {
                pattern += query[i]+'|'
            }
        }
        pattern = rtrim(pattern, '|');

        var pattern = new RegExp('('+pattern+')(?=[^>]*(<|$))', 'gi');
        var link_pattern = new RegExp('<a([^>]*)>(.*)</a>');
        var selectors = details ? '.details div.table-cell div.value, #area_listing div.table-cell div.value' : '#listings article ul.info li.title, #listings article ul.info li.fields';

        $(selectors).each(function(){
            var value = trim($(this).html());
            var href = false;
            if ( $(this).find('a').length > 0 ) {
                value = trim($(this).find('a').html());
                href = $(this).find('a').attr('href');
                className = $(this).find('a').attr('class');
            }

            //value = value.replace(/(<([^>]+)>)/ig,"");
            value = value.replace(pattern, '<span class="ks-highlight">$1</span>');
            value = href ? '<a class="'+className+'" href="'+href+'">'+value+'</a>' : value;

            $(this).html(value);
        });
    };

    /**
    * custom checkboxes, radio handler
    *
    **/
    this.customInputIndex = 1;
    this.customInput = function(){
        $('body label > input[type=checkbox]:not(.default),body label > input[type=radio]:not(.default)').each(function(){
            var name = $(this).attr('id') ? $(this).attr('id') : 'css_'+ $(this).prop('tagName') + '_' + self.customInputIndex;

            $(this).parent().prepend('<span></span>');
            $(this).attr('id', name);
            $(this).parent().attr('for', name);
            $(this).parent().before($(this));
            self.customInputIndex++;
        });
    };

    /**
    * languages selector handler
    *
    **/
    this.langSelector = function(){
        $('#lang-selector > .default').click(function(){
            if ( $('#lang-selector').hasClass('lang-mobile') ) {
                $(this).next().toggle();
            }
            else {
                $('span.circle_opened').not($(this).parent()).removeClass('circle_opened');
                $(this).parent().toggleClass('circle_opened');
            }

            flUtil.setPriorityZIndex($(this).parent());
        });

        $(document).bind('click touchstart', function(event){
            if ( !$(event.target).parents().hasClass('circle_opened') ) {
                $('#lang-selector').removeClass('circle_opened');
            }
            if ( !$(event.target).parents().hasClass('lang-mobile') ) {
                $('.lang-mobile > span.content').attr('style', '');
            }
        });
    };

    /**
    * user navigation bar
    *
    **/
    this.userNavbar = function(){
        $('#user-navbar > .default').click(function(){
            $('span.circle_opened').not($(this).parent()).removeClass('circle_opened');
            $(this).parent().toggleClass('circle_opened');

            flUtil.setPriorityZIndex($(this).parent());
        });

        $(document).bind('click touchstart', function(event){
            if ( !$(event.target).parents().hasClass('circle_opened') ) {
                $('#user-navbar').removeClass('circle_opened');
            }
        });
    };

    /**
    * keyword search handler
    *
    **/
    this.keywordSearch = function(){
        var close_handler = function(){
            if ( $('#autocomplete').val() || media_query == 'desktop' ) {
                $('#autocomplete').closest('span.field').next().show();
            }
            else {
                $('#autocomplete').closest('span.field').next().hide();
            }
        };

        $('.kw-search > span.lens').click(function(){
            if ( $(this).parent().hasClass('kw-search-mobile') ) {
                $(this).parent().find('form').submit();
            }
            else {
                if ( !$(this).parent().hasClass('kw-search-active') ) {
                    $(this).parent().addClass('kw-search-active');
                    $(this).parent().find('#autocomplete').focus();
                    $(this).parent().find('span.field').animate({width: 200}, function(){
                        $('#autocomplete').show();
                        flynaxTpl.menu();
                    });
                }
            }
        });
        $('.kw-search .close').click(function(){
            if ( $(this).parent().hasClass('kw-search-mobile') ) {
                $(this).parent().find('#autocomplete').val('');
                close_handler();
            }
            else {
                $(this).hide();
                $(this).parent().find('span.field').animate({opacity: 0}, function(){
                    var field = this;

                    $(this).parent().removeClass('kw-search-active');
                    $(this).animate({width: 0}, function(){
                        $(field).css('opacity', 1);

                        flynaxTpl.menu();
                        $('#autocomplete').hide();
                        $('.kw-search .close').show();
                    });
                });
            }
        });

        close_handler();
        $('#autocomplete').keyup(function(){
            close_handler();
        });

        $('input#autocomplete').vsAutoComplete();
    };

    /**
     * @deprecated 4.9.0
     **/
    this.shoppingCart = function(){}

    /**
     * @deprecated 4.9.3
     **/
    this.categoryTree = function(){};

    /**
     * @deprecated 4.9.1
     */
    this.picGallery = function(){};

    /**
    * textarea counter setup
    *
    **/
    this.setupTextarea = function(selector){
        $('textarea[name=contact_message]').each(function(){
            $(this).next('.textarea_counter_default').remove();
            $(this).textareaCount({
                'maxCharacterSize': rlConfig['messages_length'],
                'warningNumber': 20
            });
        });
    }

    this.contactSeller = function(){
        $('.contact-seller').each(function(){
            var box_width = $(this).attr('accesskey') == 'no-access' ? 'auto' : 400;
            $(this).flModal({
                source: '#contact_owner_form',
                width: box_width,
                height: 'auto',
                ready: function(){
                    self.setupTextarea('div.modal_content');
                }
            });
        });
    }

    /**
     * @deprecated 4.8.0
     */
    this.listingDetails = function(){}

    /**
     * @deprecated 4.8.0
     */
    this.accountDetails = function(){}

    /**
    * location fields hamdler
    *
    **/
    this.locationHandler = function(){
        var region = $('input[name="f[region]"]');
        var states = $('select[name="f[b_states]"]');
        var country = $('select[name="f[b_country]"]');

        if ( country.length <= 0 )
            return;

        var locationHandlerAction = function(){
            if ( country.val() == 'united_states' ) {
                region.closest('.submit-cell').hide();
                states.closest('.submit-cell').show();
            }
            else {
                region.closest('.submit-cell').show();
                states.closest('.submit-cell').hide();
            }
        };

        country.bind('change', locationHandlerAction);
        locationHandlerAction();
    };

    /**
     * View categories by alphabet
     */
    this.categoryAlphabet = function(){
        $('div.category-alphabet li:not(.active) > span').click(function() {
            var cont = '';
            var self = this;

            $(this).closest('ul').find('li').removeClass('active');
            $(this).parent().addClass('active');

            $('div#cat_alphabet_cont').show();

            var replace = $(this).text();
            var char = $(this).text() == '#' ? 'rest' : $(this).text();

            $('div#cat_alphabet_cont .char_cont').hide();

            if ($('div#cat_alphabet_cont #cont_' + char).length > 0) {
                $('div#cat_alphabet_cont #cont_' + char).show();
            } else {
                $('div#cat_alphabet_cont div.loading').show();

                flUtil.ajax({mode: 'category', item: char}, function(response) {
                    if (response != '' && response != null) {
                        cont = '<ul class="char_cont" id="cont_' + char + '">';
                        for (var i = 0; i < response.length; i++) {
                            cont += '<li><a title="' + response[i].name;
                            cont += ' (' + response[i].Count + ')" href="' + response[i].url + '">';
                            cont += response[i].name + '</a></li>';
                        }
                        cont += '</ul>';
                    } else {
                        cont = '<div class="char_cont" id="cont_' + char + '">';
                        cont += lang.nothing_found_for_char.replace('{char}', replace) + '</div>';
                    }

                    $('div#cat_alphabet_cont').append(cont);
                    $('div#cat_alphabet_cont div.loading').hide();

                    setPosition(self);
                });
            }

            setPosition(this);
        });

        setPosition = function(obj){
            var left = $('div.category-alphabet').position().left - 1;
            var right = $('div.category-alphabet').get(0).offsetWidth + 1 + left;

            var char_top = $(obj).get(0).offsetTop + 34;
            var char_width = $(obj).get(0).offsetWidth / 2;
            var cont_width = $('div#cat_alphabet_cont').get(0).offsetWidth;
            var half = cont_width / 2;
            var left_offset = $(obj).parent().position().left - half + char_width;
            left_offset = left_offset < left ? left : left_offset;
            left_offset = left_offset + cont_width > right ? right - cont_width : left_offset;
            $('div#cat_alphabet_cont').css({left: left_offset+'px',top: char_top+'px'});
        };

        $(document).bind('click touchstart', function(event){
            if ( !$(event.target).parents().hasClass('category-alphabet') ) {
                $('div#cat_alphabet_cont').hide();
                $('div.category-alphabet li.active').removeClass('active');
            }
        });
    };

    this.tabsMore = function(){
        $tabs = $('ul.tabs');

        $tabs.each(function(){
            var $tabsContainer = $(this);
            var tmSelf         = this; // reference to local method environment
            var width          = $tabsContainer.width();
            var height         = $tabsContainer.height();
            var count          = $tabsContainer.find('> li:not(.more,.overflowed)').length;
            var sum            = 0;
            var overflowed     = false;
            var windowWidth    = 0;

            if ($tabsContainer.is(':hidden')) {
                $tabsContainer.addClass('tabs-hidden');
            }

            this.init = function(){
                $tabsContainer.find('> li:not(.more,.overflowed)').each(function(){
                    var index = $tabsContainer.find('> li').index(this) + 1;
                    var button = index == count ? 0 : height;

                    sum += $(this).outerWidth(true);

                    if (sum + button > width) {
                        if (!overflowed) {
                            tmSelf.more($(this).parent());
                        }
                        $(this).parent().find('li.overflowed > ul').append($(this));
                        overflowed = true;
                    }
                });

                $tabsContainer.addClass('tabs-ready');
            };

            this.more = function(parent){
                parent.append('<li class="more"><span></span><span></span><span></span></li><li class="overflowed"><ul></ul></li>');
            };

            this.expand = function(){
                var tabsExpander = function(){
                    $tabsContainer.addClass('current');

                    $tabs.each(function(){
                        $(this).not('.current').find('> li.opened').removeClass('opened');
                    });

                    var $moreLi       = $tabsContainer.find('> li.more');
                    var $overflowedLi = $tabsContainer.find('> li.overflowed');
                    $moreLi[$moreLi.hasClass('opened') ? 'removeClass' : 'addClass']('opened');
                    $overflowedLi[$overflowedLi.hasClass('opened') ? 'removeClass' : 'addClass']('opened');

                    $tabsContainer.removeClass('current');
                };
                $tabsContainer.find('> li.more').unbind('click', tabsExpander).bind('click', tabsExpander);
            };

            this.tabsResize = function(){
                if (windowWidth == window.innerWidth) {
                    return;
                }

                $tabsContainer.find('> li.more').remove();
                $tabsContainer.find('> li.overflowed > ul > li').each(function(){
                    $tabsContainer.append($(this));
                });
                $tabsContainer.find('> li.overflowed').remove();

                width      = $tabsContainer.width();
                sum        = 0;
                overflowed = false;

                tmSelf.init();
                tmSelf.expand();

                windowWidth = window.innerWidth;
            };

            this.init();
            this.expand();

            $(document).bind('mouseup touchstart', function(event){
                var container = $tabsContainer.find('> li.overflowed');

                // Trigger resize for the hidden tabs
                if ($tabsContainer.is(':hidden')) setTimeout(function(){ $(window).trigger('resize'); }, 1);

                if (!container.is(event.target)
                    && container.has(event.target).length === 0
                    && !$(event.target).hasClass('more')
                    && !$(event.target).parent().hasClass('more')
                ) {
                    $tabsContainer.find('> li.opened').removeClass('opened');
                }
            });

            $(window).bind('resize', function(){
                tmSelf.tabsResize();

                if ($tabsContainer.is(':hidden')) {
                    windowWidth = 0;
                }
            });

            setTimeout(function(){
                windowWidth = 0;
                tmSelf.tabsResize();
            }, 1);

            $(window).on('hashchange', function(){
                if ($tabsContainer.hasClass('tabs-hidden')) {
                    windowWidth = 0;
                    $tabsContainer.resize();
                    $tabsContainer.removeClass('tabs-hidden');
                }
            });
        });
    };

    this.afterListingsAjaxLoad = function(){
        flFavoritesHandler();
    };

    /**
     * @deprecated 4.9.0
     */
    this.contactOwnerSubmit = function() {};

    /**
     * @deprecated 4.9.0
     */
    this.realtyPropType = function(){}

    /**
     * @deprecated 4.9.0
     */
    this.searchFormPage = function(){}

    /**
     * @deprecated 4.8.2
     */
    this.searchFormBox = function() {};

    /**
     * @deprecated 4.7.0 - hisrc() library has been removed
     */
    this.featured = function(){};
    this.hisrc = function(){};
};

var flynaxTpl = new flynaxTplClass();

/**
*
* toggle container by clicking some element
*
**/
(function($) {
    $.fn.tplToggle = function(options) {
        var settings = $.extend({
            cont: false, // container to toggle
            parent : false, // parent element to detect on body click
            id: false // id to save state
        }, options);

        var self = this;

        if ( settings.id ) {
            if ( parseInt(readCookie(settings.id)) ) {
                $(settings.cont).toggle();
                self.toggleClass('active');
            }
        }

        this.click(function(){
            if ( $(this).hasClass('disabled') )
                return false;

            $(settings.cont).toggle();
            self.toggleClass('active');

            if ( settings.id ) {
                createCookie(settings.id, self.hasClass('active') ? 1 : 0, 365);
            }
        });

        if ( settings.parent ) {
            $(document).bind('click touchstart', function(event){
                if ( !$(event.target).parents().hasClass(settings.parent) ) {
                    $(settings.cont).hide();
                    self.removeClass('active');
                }
            });
        }
    };
}(jQuery));

/**
*
* autocomplete plugin
*
* @package jQuery
*
**/
(function($) {
    $.fn.vsAutoComplete = function(options){
        options = jQuery.extend({
            type: '*',
            field: false
        },options)

        var query = '';
        var obj = this;
        var set = false;
        var cur_item = 0;
        var total = 0;
        var interface = '<div id="ac_interface" class="scrollbar"></div>';
        var save_pos = 0;
        var timer = 0;

        var key_enter = 13;
        var key_down = 40;
        var key_up = 38;
        var del_up = 46;
        var poss = 0;

        if ( $(obj).parents().hasClass('kw-search-mobile') )
            return;

        $('#ac_interface').remove();
        $(obj).attr('autocomplete', 'off');

        var request = function(e)
        {
            if( (query != $(obj).val() && e.keyCode != key_enter) || (e.keyCode == key_down && cur_item == 0) || e.type == 'click' )
            {
                if ( $(obj).val().length < 2 )
                {
                    $('#ac_interface').hide();
                    return false;
                }

                /* build interface */
                if ( !set )
                {
                    $(obj).after(interface);
                    set = true;
                }

                if ( e.keyCode != key_down )
                {
                    cur_item = 0;
                }

                /* do complete */
                $.getJSON(rlConfig['ajax_url'], {mode: 'listing', item: $(obj).val(), field: options.field, type: options.type, lang: rlLang}, function(response){
                    if( response != '' && response != null )
                    {
                        var content = '<ul>';
                        var index = 0;
                        var query_arr = query.split(' ');

                        for ( var i = 0; i < response.length; i++ )
                        {
                            if ( response[i]['listing_title'] != 'listing' && response[i]['listing_title'] != '' )
                            {
                                var out = response[i]['listing_title'];

                                for ( var it = 0; it < query_arr.length; it++ )
                                {
                                    if ( query_arr[it] != '' && query_arr[it].toLowerCase() != 'b' && query_arr[it].length > 2 )
                                    {
                                        if ( response[i]['listing_title'].toLowerCase().indexOf(query_arr[it].toLowerCase()) >= 0 )
                                        {
                                            var replace = '';

                                            var ix = response[i]['listing_title'].toLowerCase().indexOf(query_arr[it].toLowerCase());

                                            for ( var j = 0; j< query_arr[it].length; j++ )
                                            {
                                                if ( response[i]['listing_title'][ix] != query_arr[it][j] )
                                                {
                                                    replace += query_arr[it].charAt(j).toUpperCase();
                                                }
                                                else
                                                {
                                                    replace += query_arr[it].charAt(j);
                                                }

                                                ix++;
                                            }

                                            var search = new RegExp(query_arr[it], 'gi');
                                            out = out.replace(search, '<b>'+replace+'</b>' );
                                        }
                                    }
                                }

                                if ( replace != '' )
                                {
                                    index++;
                                    content += '<li id="ac_item_'+index+'" class="d-flex"><div class="ac-listing"><a title="'+view_details+'" href="'+response[i]['Listing_path']+'" target="_blank">'+out+'</a></div><div class="ac-category flex-fill"><a title="'+response[i]['Category_name']+'" href="'+response[i]['Category_path']+'" target="_blank">'+response[i]['Category_name']+'</a></div></li>';
                                }
                            }
                        }

                        content += '</ul>';

                        total = index;

                        $('#ac_interface').html(content);
                        $('#ac_interface').show();

                        $('#ac_interface div:not(.ac_header)').click(function(){
                            save_pos = poss = 0;
                        });

                        $('#ac_interface li').mouseover(function(){
                            $(this).addClass('active');
                        }).mouseout(function(){
                            $(this).removeClass('active');
                        });
                    }
                    else
                    {
                        $('#ac_interface').hide();
                    }
                });
                query = $(obj).val();
            }
        }

        /* keydown handler */
        $(obj).bind('keyup', function(e){
            if ( $(obj).parents().hasClass('kw-search-mobile') )
                return;

            if ( e.keyCode == key_enter )
            {
                if (cur_item != 0)
                {
                    location.href = $('#ac_item_'+cur_item).find('a').attr('href');
                }
            }
            else if ( e.keyCode == key_down )
            {
                if ( cur_item < total && $('#ac_interface').is(':visible') )
                {
                    cur_item++;
                    poss += 31;
                    drow();
                }
            }
            else if ( e.keyCode == key_up && $('#ac_interface').is(':visible') )
            {
                if (cur_item > 1)
                {
                    cur_item--;
                    poss -= 31;
                    drow('up');
                }
            }
            else if ( e.keyCode == del_up )
            {
                $('#ac_interface').hide();
                $('#ac_interface ul').remove();
            }

            clearTimeout(timer);
            timer = setTimeout(function(){
                request(e);
            }, 600);
        })

        $(obj).click(function(){
            if ( $(obj).parents().hasClass('kw-search-mobile') )
                return;

            if ( $('#ac_interface ul').length > 0 )
            {
                $('#ac_interface').show();
            }
        });

        $('body').click(function(e){
            if ( $(e.target).attr('id') != 'autocomplete' && $(e.target).parent().attr('class') != 'ac-category' )
            {
                $('#ac_interface').hide();
            }
        });

        function drow(direction)
        {
            $('#ac_interface li.active').removeClass('active');
            $('#ac_item_'+cur_item).addClass('active');

            $('#ac_interface').animate({scrollTop:poss-62}, 100);
        }
    }
})(jQuery);

/**
*
* tabs click handler
*
* @param object obj - tab object referent
*
**/
$(document).ready(function(){
    $('.tabs:not(.tabs-hash) li:not(.more,.overflowed)').click(function(){
        tabsSwitcher(this);
    });

    hashTabs();
});

var tabsSwitcher = function(obj){
    if ($(obj).length <= 0) {
        return;
    }

    var tab_key = $(obj).attr('id').split('_').slice(1, $(obj).attr('id').split('_').length).join('_'),
        tab_instance = $(obj).closest('ul.tabs');

    $(tab_instance).find('li:not(.more, .overflowed)').each(function(){
        var key = $(this).attr('id').split('_').slice(1, $(this).attr('id').split('_').length),
            key_string = key.join('_');
        $('div#area_'+key_string).hide();
    });

    $(tab_instance).find('li.active').removeClass('active');

    $(obj).addClass('active');
    $('div#area_'+tab_key).show();
};

var hashTabs = function(){
    hashTabsSwitcher(window.location.hash);

    $('.tabs.tabs-hash li:not(.more,.overflowed) a').off().on('click', function(e){
        e.preventDefault();
        hashTabsSwitcher(this);
    });
}

var hashTabsSwitcher = function(obj){
    if (!obj) {
        return;
    }

    var $obj;
    var postfix = '_tab';

    if (typeof(obj) === 'string') {
        var key = obj.replace(postfix, '');
        $obj = $('ul.tabs-hash a[href="' + key + '"]');

        // prevent for second call in window hash
        if (!$obj.length || $obj.parent().hasClass('active')) {
            return;
        }
    } else {
        $obj = $(obj);
    }

    var tab_key = $obj.data('target');
    var tab_instance = $obj.closest('ul.tabs');

    $(tab_instance).find('li:not(.more, .overflowed) a').each(function(){
        var key = $(this).data('target');
        $(tab_instance).parent().find('div#area_' + key).hide();
    });

    $(tab_instance).find('li.active').removeClass('active');

    $obj.parent().addClass('active');
    $('div#area_' + tab_key).show();

    window.location.hash = tab_key + postfix;
};

/**
 *
 * favorites handler
 *
 **/
$(document).ready(function(){
    flFavoritesHandler();
});

var flFavoritesHandler = function(){

    /* favorites handler */
    $('.favorite').each(function(){
        var id = $(this).attr('id').split('_')[1];
        var ids = readCookie('favorites');

        if (ids) {
            ids = decodeURIComponent(ids);
            ids = ids.split(',');

            if (ids.indexOf(id) >= 0) {
                $(this).removeClass('add').addClass('remove');
                $(this).attr('title', lang['remove_from_favorites']).find('span.link').text(lang['remove_from_favorites']);
            }
        }
    });

    $('.favorite').unbind('click').click(function(){
        var id = $(this).attr('id').split('_')[1];
        var ids = readCookie('favorites');

        if (ids) {
            ids = decodeURIComponent(ids);
            ids = ids.split(',');

            if (ids.indexOf(id) >= 0) {
                ids.splice(ids.indexOf(id), 1);
                createCookie('favorites', ids.join(','), 93);

                $(this).removeClass('remove').addClass('add');
                $(this).attr('title', lang['add_to_favorites']).find('span.link').text(lang['add_to_favorites']);

                removeFromFavorites(id, true);

                if (rlPageInfo['key'] == 'my_favorites') {
                    $(this).closest('article').fadeOut(function(){
                        $(this).remove();
                        printMessage('notice', lang['notice_listing_removed_from_favorites']);

                        if ($('#listings > article').length < 1) {
                            if ($('ul.pagination').length > 0) {
                                location.href = rlPageInfo.url;
                            } else {
                                var div = '<div class="info">' + lang['no_favorite'] + '</div>';
                                $('#controller_area').append(div);
                                $('.grid_navbar').remove();
                            }
                        }
                    });
                }

                return;
            }
            else {
                ids.push(id);
                addToFavorite(id);
            }
        }
        else {
            ids = new Array();
            ids.push(id);
            addToFavorite(id);
        }

        createCookie('favorites', ids.join(','), 93);

        $(this).removeClass('add').addClass('remove');
        $(this).attr('title', lang['remove_from_favorites']).find('span.link').text(lang['remove_from_favorites']);
    });
}

/**
 * Add listing to favorites list
 * @param {int} id
 */
var addToFavorite = function(id){
    ajaxFavorite(id);
};

/**
 * Remove listing from favorites list
 * @param {int} id
 */
var removeFromFavorites = function(id){
    ajaxFavorite(id, true);
};

/**
 * Adding/removing listing in favorites list (for logged users only)
 * @param {int}  id
 * @param {bool} delete_action
 */
var ajaxFavorite = function(id, delete_action){
    if (isLogin) {
        $.post(rlConfig['ajax_url'], {mode: 'ajaxFavorite', id: id, delete: delete_action});
    }
};

/**
* notices/errors handler
*
* @param string type - message type: error, notice, warning
* @param string/array message - message text
* @param string/array fields - error fields names, array or comma separated
* @param direct - DEPRECATED
*
**/
var PMtimer = false;
var printMessage = function(type, message, fields, direct){
    if ( !message || !type )
        return;

    var self  = this;
    var types = new Array('error', 'notice', 'warning');
    var height = 0;
    var from_top = false;

    var time = 20; //seconds
    time *= 1000;

    if ( types.indexOf(type) < 0 )
        return;

    this.isFixed = function(){
        var offset = $('.header-banner-cont').length > 0 ? $('.header-banner-cont').get(0).offsetHeight : 0;
        var selector = media_query == 'desktop' ? 'body > header > section.point1' : 'body > header';
        return $(document).scrollTop() > ($(selector).height() + offset) ? true : false;
    };

    this.getHeight = function(){
        var offset = $('.header-banner-cont').length > 0 ? $('.header-banner-cont').get(0).offsetHeight : 0;
        return media_query != 'mobile' ? $('body > header').height() + offset : $('body > header > section.point1').height() + offset;
    };

    this.build = function(){
        this.closeMessage();

        var offset = this.isFixed() && media_query == 'desktop' ? 58 : 0;
        var addClass = this.isFixed() ? ' fixed' : '';

        if ( $('body > div#modal_mask').length > 0 ) {
            addClass += ' top';
            this.from_top = true;
        }

        var html = ' \
            <div class="notification '+type+addClass+' hide priority-z-index"><div> \
                <div class="message">'+message+'</div> \
                <div class="close close-black" title="'+lang['close']+'"><div></div></div> \
            </div></div> \
        ';

        if ($('body > *:first').hasClass('tmp-hidden')) {
            $('body').append(html);
        } else {
        $('section#main_container').prepend(html);
        }
        height = $('.notification').height() + 70 - offset; //70 is shadow height

        flUtil.setPriorityZIndex($('.notification'));

        if ( this.from_top ) {
            offset = 0;
        }

        $('.notification').css('top', '-'+height+'px').show().animate({top: offset}, 'slow', function(){
            if ( self.isFixed() ) {
                $(this).addClass('done');
            }
        });

        PMtimer = setTimeout(self.closeMessage, time);
    };

    this.closeMessage = function(build){
        clearTimeout(PMtimer);

        var top = $('.notification').hasClass('fixed') && media_query == 'desktop' ? 58 : 0;

        $('.notification').animate({top: 50 + top, opacity: 0}, function(){
            $('.notification').remove();
            if ( build ) {
                self.build();
            }
        });
    };

    if ( typeof(message) == 'object' ) {
        var tmp = '<ul>';
        for( var i=0; i<message.length; i++ ) {
            tmp += '<li>'+message[i]+'</li>';
        }
        tmp += '</ul>';
        message = tmp;
    }

    $('input,select,textarea,table.error').removeClass('error');

    /* highlight error fields */
    if ( fields ) {
        if ( typeof(fields) != 'object' ) {
            fields = fields.split(',');
        }

        for ( var i = 0; i<fields.length; i++ ) {
            if ( !fields[i] )
                continue;

            if ( trim(fields[i]) != '' ) {
                if ( fields[i].charAt(0) == '#' ) {
                    $(fields[i]).addClass('error');
                }
                else {
                    var selector = 'input[name="' + fields[i] + '"],input[name^="' + fields[i] + '"]:last:not(.policy),select[name="' + fields[i] + '"],textarea[name="' + fields[i] + '"]';
                    if ( $(selector).length > 0 && $(selector).attr('type') != 'radio' && $(selector).attr('type') != 'checkbox' ) {
                        $(selector).addClass('error');
                    }
                    else {
                        if ( $(selector).attr('type') == 'radio' || $(selector).attr('type') == 'checkbox' ) {
                            $(selector).closest('div.field').addClass('error');
                        }
                        else {
                            $('input[name="'+fields[i]+'"],select[name="'+fields[i]+'"],textarea[name^="'+fields[i]+'"]').parent().addClass('error');
                        }
                    }
                }
            }
        }

        var removeHighlightFromParent = function () {
            $(this).closest('div.error').removeClass('error');
        };

        $('div.field.error input')
            .off('click', removeHighlightFromParent)
            .on('click', removeHighlightFromParent);

        var removeHighlightFromField = function () {
            $(this).removeClass('error');
        };

        $('input,select,textarea,table.error')
            .off('focus', removeHighlightFromField)
            .on('focus', removeHighlightFromField);
    }

    if ( $('.notification').length > 0 ) {
        self.closeMessage(true);
    }
    else {
        self.build();
    }

    $('.notification div.close').live('click', function(){
        self.closeMessage();
    });

    $('.notification').mouseenter(function(){
        clearTimeout(PMtimer);
    }).mouseleave(function(){
        PMtimer = setTimeout(self.closeMessage, time);
    });

    var notificationScroll = function(){
        if ( this.isFixed() ) {
            $('.notification').addClass('fixed done');
        }
        else {
            $('.notification').removeClass('hide fixed done').removeAttr('style');
        }
    };
    $(window).bind('touchmove scroll', notificationScroll);
};

var flFieldset = function(){
    var open = function(fieldset, quick) {
        if (quick) {
            fieldset.find('div.body').show();
            fieldset.find('span.arrow').removeClass('up');
        } else {
            fieldset.find('div.body').slideDown(function(){
                fieldset.find('span.arrow').removeClass('up');
            });
        }
    }

    var close = function(fieldset, quick) {
        if (quick) {
            fieldset.find('div.body').hide();
            fieldset.find('span.arrow').addClass('up');
        } else {
            fieldset.find('div.body').slideUp(function(){
                fieldset.find('span.arrow').addClass('up');
            });
        }
    }

    var add = function(cookies, id) {
        if (cookies) {
            if (cookies.indexOf(id) < 0) {
                cookies.push(id);
                id = cookies.join(',');
            }
        }
        createCookie('fieldset', id, 62);
    }

    var remove = function(cookies, id) {
        cookies.splice(cookies.indexOf(id), 1);
        createCookie('fieldset', cookies.join(','), 62);
    }

    $('div.fieldset header .arrow').unbind('click').click(function(){
        var parent = $(this).closest('.fieldset');
        var id = $(parent).attr('id');
        var cookies = readCookie('fieldset') ? readCookie('fieldset').split(',') : Array();

        // close
        if (parent.find('div.body').is(':visible')) {
            close(parent);

            if (parent.hasClass('hidden-default')) {
                remove(cookies, id);
            } else {
                add(cookies, id);
            }
        }
        // open
        else {
            open(parent);

            if (parent.hasClass('hidden-default')) {
                add(cookies, id);
            } else {
                remove(cookies, id);
            }
        }
    });

    $('div.fieldset').each(function(){
        var id = $(this).attr('id');
        var cookies = readCookie('fieldset') ? readCookie('fieldset').split(',') : Array();

        // close quick
        if (($(this).hasClass('hidden-default') && cookies.indexOf(id) < 0) || (!$(this).hasClass('hidden-default') && cookies.indexOf(id) >= 0)) {
            close($(this), true);
        }
        // open quick
        else if (($(this).hasClass('hidden-default') && cookies.indexOf(id) >= 0) || (!$(this).hasClass('hidden-default') && cookies.indexOf(id) < 0)) {
            open($(this), true);
        }
    });
}

/**
*
* hide or show the object (via jQuery effect) by ID, and hide all objects by html path
*
* @param srting id - field id
* @param srting path - html path
*
**/
function show(id, path) {
    if (path != undefined) {
        $(path).slideUp('fast');
    }

    if ($('#' + id).css('display') == 'block') {
        $( '#'+id ).slideUp('normal');
    }
    else {
        $( '#'+id ).slideDown('slow');
    }
}

/**
*
* trim string
*
* @param string str - string for trim
* @param string chars - chars to be trimmed
*
* @return trimmed string
*
**/
function trim(str, chars) {
    return ltrim(rtrim(str, chars), chars);
}

/**
*
* left trim string
*
* @param string str - string for trim
* @param string chars - chars to be trimmed
*
* @return trimmed string
*
**/
function ltrim(str, chars) {
    if ( !str )
        return;

    chars = chars || "\\s";
    return str.replace(new RegExp("^[" + chars + "]+", "g"), "");
}

/**
*
* right trim string
*
* @param string str - string for trim
* @param string chars - chars to be trimmed
*
* @return trimmed string
*
**/
function rtrim(str, chars) {
    if ( !str )
        return;

    chars = chars || "\\s";
    return str.replace(new RegExp("[" + chars + "]+$", "g"), "");
}

/**
*
* escape or replace quotes
*
* @param string str - string for replacing
* @param bool to - replace if true and escape if false
*
**/
function quote( str, to ) {
    if (!to) {
        return str.replace(/'/g, "").replace(/"/g, "");
    }
    else {
        var to_single = '&rsquo;';
        var to_double = '&quot;';

        return str.replace(/'/g, to_single).replace(/"/g, to_double).replace(/\n/g, '<br />' );
    }
}

/**
 * @deprecated 4.7.0 - hisrc() library has been removed
 */
var caroselCallback = function(){};
