import{r as e,o as a,h as l,f as s,t as o,c as r,i as t,a as i,w as n,d,b as u}from"./app-667dfa05.js";const m={class:"loginWrapper d-flex"},c={class:"--cover w-66 h-100"},p=["src"],g={class:"--mainContent w-25 ps-16 pt-8 pb-4 h-100 d-flex flex-column justify-space-between"},b={class:"--top"},f=["src"],h={class:"--middle"},v={class:"mb-12"},y={class:"--bottom"},w={class:"--copyright pa-4"},V=["innerHTML"],x={data(){return{form:!1,username:null,password:null,loading:!1,titleLogin:this.data.title?this.data.title:"Sign in to Minimal"}},methods:{onSubmit(){if(!this.form)return;this.loading=!0;const e={username:this.username,password:this.password};this.$inertia.post("/cms/login",e,{onStart:()=>this.loading=!0,onFinish:()=>this.loading=!1})},required:e=>!!e||"Field is required"}},S=Object.assign(x,{__name:"Login",props:{error:String,data:{type:Object,default:{background:"/bundles/corepulse/image/corepulse.png",logo:"/bundles/corepulse/image/corepulse.png",color:"#4CAF50",footer:""}}},setup(x){return(S,k)=>{const L=e("v-alert"),j=e("Input"),q=e("Password"),U=e("v-btn"),_=e("v-form");return a(),l("div",m,[s("div",c,[s("img",{src:x.data.background?x.data.background:"/bundles/pimcoreadmin/img/login/pc11.svg",alt:"Background Image"},null,8,p)]),s("div",g,[s("div",b,[s("img",{src:x.data.logo?x.data.logo:"/bundles/corepulse/image/corepulse.png",alt:"",class:"--logo mb-4"},null,8,f)]),s("div",h,[s("h1",v,o(S.titleLogin),1),x.error?(a(),r(L,{key:0,color:"error",class:"flex-0-0 mb-4",text:x.error,icon:"mdi-alert-circle-outline",variant:"tonal"},null,8,["text"])):t("",!0),i(_,{modelValue:S.form,"onUpdate:modelValue":k[2]||(k[2]=e=>S.form=e),onSubmit:u(S.onSubmit,["prevent"])},{default:n((()=>[i(j,{label:"Username",modelValue:S.username,"onUpdate:modelValue":k[0]||(k[0]=e=>S.username=e),readonly:S.loading,class:"mb-2",density:"default",clearable:"","hide-details":"",rules:[S.required],variant:"outlined"},null,8,["modelValue","readonly","rules"]),i(q,{modelValue:S.password,"onUpdate:modelValue":k[1]||(k[1]=e=>S.password=e),readonly:S.loading,class:"mb-2",clearable:"","hide-details":"",density:"default",rules:[S.required],label:"Password",variant:"outlined",type:"password"},null,8,["modelValue","readonly","rules"]),i(U,{disabled:!S.form,loading:S.loading,block:"",class:"btn-primary",size:"x-large",type:"submit",variant:"elevated"},{default:n((()=>[d(" Login ")])),_:1},8,["disabled","loading"])])),_:1},8,["modelValue","onSubmit"])]),s("div",y,[s("article",w,[s("div",{innerHTML:this.data.footer},null,8,V)])])])])}}});export{S as default};