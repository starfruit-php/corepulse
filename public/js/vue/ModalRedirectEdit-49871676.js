import{_ as e,k as t,r as l,o as a,h as o,a as n,w as u,F as r,i as s,d as i}from"./app-043f1e93.js";const d=e({props:{item:{type:[Object,Array],default:{}}},data(){return{internalValue:this.isNotEmpty(this.item)?this.item:{type:null,source:null,sourceSite:null,target:null,targetSite:null,statusCode:null,priority:null,regex:null,passThroughParameters:null,active:null,expiry:null},type:this.isNotEmpty(this.item)?"Update":"Create",toast:t.useToast({position:"top-right",style:{zIndex:"200"}})}},mounted(){},methods:{isNotEmpty:e=>Array.isArray(e)?e.length>0:"object"==typeof e&&Object.keys(e).length>0,closeDialog(){this.$emit("update:dialog",!1)},save(){const e=new FormData;for(var[t,l]of(e.append("action",this.type),Object.entries(this.internalValue)))null!=l&&e.append(t,l);fetch(route("seo_redirect_detail"),{method:"POST",body:e}).then((e=>{if(e.ok)return e.json();throw new Error("Lỗi xảy ra khi gửi yêu cầu.")})).then((e=>{e.success?(setTimeout((()=>{this.toast.open({message:e.message,type:"success"})}),1e3),this.$emit("update:dialog",!1),this.$emit("update:loading",!1),this.$emit("update:componentLoad",(new Date).getTime())):setTimeout((()=>{this.toast.open({message:e.message,type:"warning"})}),1e3)})).finally((()=>{}))}}},[["render",function(e,t,d,p,m,c){const V=l("v-toolbar-title"),y=l("v-toolbar"),h=l("Select"),g=l("Input"),b=l("v-checkbox"),v=l("v-card-text"),f=l("v-btn"),_=l("v-card-actions");return a(),o(r,null,[n(y,{density:"compact"},{default:u((()=>[n(V,{text:m.type+" Redirect",class:"text-red-darken-1"},null,8,["text"])])),_:1}),n(v,{class:"py-8"},{default:u((()=>["Create"==m.type?(a(),o(r,{key:0},[n(h,{label:"Type",modelValue:m.internalValue.type,"onUpdate:modelValue":t[0]||(t[0]=e=>m.internalValue.type=e),route:"seo_redirect_type_option"},null,8,["modelValue"]),n(g,{label:"Source",modelValue:m.internalValue.source,"onUpdate:modelValue":t[1]||(t[1]=e=>m.internalValue.source=e)},null,8,["modelValue"]),n(g,{label:"Target",modelValue:m.internalValue.target,"onUpdate:modelValue":t[2]||(t[2]=e=>m.internalValue.target=e)},null,8,["modelValue"])],64)):"Update"==m.type?(a(),o(r,{key:1},[n(h,{label:"Type",modelValue:m.internalValue.type,"onUpdate:modelValue":t[3]||(t[3]=e=>m.internalValue.type=e),route:"seo_redirect_type_option"},null,8,["modelValue"]),n(g,{label:"Source",modelValue:m.internalValue.source,"onUpdate:modelValue":t[4]||(t[4]=e=>m.internalValue.source=e)},null,8,["modelValue"]),n(g,{label:"Target",modelValue:m.internalValue.target,"onUpdate:modelValue":t[5]||(t[5]=e=>m.internalValue.target=e)},null,8,["modelValue"]),n(h,{label:"Status Code",modelValue:m.internalValue.statusCode,"onUpdate:modelValue":t[6]||(t[6]=e=>m.internalValue.statusCode=e),route:"seo_redirect_type"},null,8,["modelValue"]),n(b,{label:"Active",modelValue:m.internalValue.active,"onUpdate:modelValue":t[7]||(t[7]=e=>m.internalValue.active=e)},null,8,["modelValue"])],64)):s("",!0)])),_:1}),n(_,{class:"justify-end"},{default:u((()=>[n(f,{onClick:t[8]||(t[8]=e=>c.closeDialog()),variant:"text"},{default:u((()=>[i("Close ")])),_:1}),n(f,{variant:"elevated",onClick:t[9]||(t[9]=e=>c.save())},{default:u((()=>[i(" Save ")])),_:1})])),_:1})],64)}]]);export{d as default};