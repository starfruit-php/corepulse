import{_ as a,o as s,h as t,f as e,F as i,l as c,C as l,t as n,D as r}from"./app-f408fcfe.js";const b={class:"nav-tabs"},o={class:"tab-list"},v=["onClick"],u={class:"tab-content"};const d=a({props:{tabs:{type:Array,required:!0}},data(){return{activeTab:this.tabs[0]}},methods:{selectTab(a){this.activeTab=a},isActive(a){return this.activeTab===a}}},[["render",function(a,d,p,T,f,h){return s(),t("div",b,[e("ul",o,[(s(!0),t(i,null,c(p.tabs,(a=>(s(),t("li",{key:a.name,class:l({active:h.isActive(a)}),onClick:s=>h.selectTab(a)},n(a.name),11,v)))),128))]),e("div",u,[r(a.$slots,"default",{activeTab:f.activeTab})])])}]]);export{d as default};