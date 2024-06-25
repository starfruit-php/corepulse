import{g as e}from"./@ckeditor-fa9b3055.js";
/*!
 * escape-html
 * Copyright(c) 2012-2013 TJ Holowaychuk
 * Copyright(c) 2015 Andreas Lubbe
 * Copyright(c) 2015 Tiancheng "Timothy" Gu
 * MIT Licensed
 */var r=/["'&<>]/;const a=e((function(e){var a,t=""+e,s=r.exec(t);if(!s)return t;var c="",n=0,o=0;for(n=s.index;n<t.length;n++){switch(t.charCodeAt(n)){case 34:a="&quot;";break;case 38:a="&amp;";break;case 39:a="&#39;";break;case 60:a="&lt;";break;case 62:a="&gt;";break;default:continue}o!==n&&(c+=t.substring(o,n)),o=n+1,c+=a}return o!==n?c+t.substring(o,n):c}));export{a as e};
