import{d as t}from"./vue-toast-notification-f2a3de7a.js";import{_ as e}from"./app-f8aa4d4b.js";import{am as i,o as s,c as o,k as r,q as a,a as n,M as p,N as m,K as l}from"./@vue-9ee83fb6.js";import"./@ckeditor-fa9b3055.js";import"./vue-abe8eda6.js";import"./vue-draggable-next-3bc70602.js";import"./unidecode-5a54da0f.js";import"./ckeditor5-custom-build-7f9f8982.js";import"./vue3-treeview-e35ab4eb.js";import"./vue-filepond-82332930.js";import"./filepond-136e7cc4.js";import"./filepond-plugin-image-preview-4f29f0b3.js";import"./filepond-plugin-file-validate-type-324437b8.js";import"./mapbox-gl-38e32bee.js";import"./@vueuse-3c3b27e0.js";/* empty css             */import"./vuetify-193e6d04.js";import"./@inertiajs-d76518b1.js";import"./axios-3d65c6bd.js";import"./deepmerge-3a6ffb65.js";import"./side-channel-e57be611.js";import"./get-intrinsic-878e88ff.js";import"./has-symbols-456daba2.js";import"./has-proto-4a87f140.js";import"./function-bind-afbcd6f2.js";import"./hasown-c3b72c9b.js";import"./call-bind-36d0f176.js";import"./set-function-length-801a4962.js";import"./define-data-property-daf1763a.js";import"./has-property-descriptors-2aeb73fe.js";import"./gopd-61b1e1fb.js";import"./object-inspect-0fa0b9de.js";import"./nprogress-9c4c7d08.js";import"./lodash.clonedeep-c1370659.js";import"./lodash.isequal-feeae999.js";import"./@vuepic-f846cd73.js";import"./date-fns-421fcf3e.js";import"./@babel-0d6f2215.js";import"./element-plus-e3c76eb0.js";import"./lodash-es-fb3d0246.js";import"./@element-plus-283d896c.js";import"./@popperjs-2d1e819d.js";import"./@ctrl-91de2ec7.js";import"./dayjs-7398d4dd.js";import"./async-validator-8d480e59.js";import"./memoize-one-63ab667a.js";import"./escape-html-5c28ffbb.js";import"./normalize-wheel-es-3222b0a2.js";import"./@floating-ui-24fcdcc4.js";import"./vue3-apexcharts-663397fa.js";import"./apexcharts-4d1d416c.js";import"./vue-i18n-d397f996.js";import"./@intlify-90b40ed7.js";const d=e({props:{item:[Object,Array],url:{type:[Object,Array,String],default:""},limit:Number,params:[Object,Array],id:Number},data(){var e="",i="",s="";"string"!=typeof this.url&&this.url.content&&(e=this.url.content.content,i=this.url.content.confirm,s=this.url.content.title);const o=new FormData;return o.append("id",JSON.stringify(this.item)),fetch(route("user_check_assigned"),{method:"POST",body:o}).then((t=>{if(t.ok)return t.json();throw new Error("Lỗi xảy ra khi gửi yêu cầu.")})).then((t=>{this.userRole=t.data,console.log(this.userRole)})).catch((t=>{})),{userRole:"",content:e,title:s,confirm:i,toast:t.useToast({position:"top-right",style:{zIndex:"200"}})}},methods:{closeDialog(){this.$emit("update:dialog",!1)},deleteTrans(){var t=[];if(this.item){this.item.forEach(((e,i)=>{t.push(e)}))}const e=new FormData;Array.isArray(t)?t.map((t=>{e.append("id[]",t)})):e.append("id",t);let i="",s={};"string"==typeof this.url?i=this.url:(i=this.url.path,this.url.data&&(s=JSON.parse(JSON.stringify(this.url.data)))),fetch(route(i,s),{method:"POST",body:e}).then((t=>{if(t.ok)return t.json();throw setTimeout((()=>{this.toast.open({message:"ERROR",type:"warning"})}),1e3),new Error("Có lỗi khi gửi yêu cầu POST.")})).then((t=>{t.warning?setTimeout((()=>{this.toast.open({message:t.warning,type:"warning"})}),1e3):(setTimeout((()=>{this.toast.open({message:"Update success",type:"success"})}),1e3),this.$emit("update:componentLoad",(new Date).getTime()),this.$emit("update:dialog",!1),this.$emit("update:limit",this.limit),this.$emit("update:selected",[]))})).catch((t=>{setTimeout((()=>{this.toast.open({message:"ERROR",type:"warning"})}),1e3),console.error(t)}))}}},[["render",function(t,e,d,u,c,j){const h=i("v-toolbar-title"),f=i("v-toolbar"),g=i("v-card-text"),y=i("v-btn"),v=i("v-card-actions");return s(),o(l,null,[r(f,{color:"red-lighten-5",density:"compact"},{default:a((()=>[r(h,{text:c.title,class:"text-red-darken-1"},null,8,["text"])])),_:1}),r(g,{class:"py-8"},{default:a((()=>[n("b",null,p(c.userRole),1),m(" "+p(c.content),1)])),_:1}),r(v,{class:"justify-end"},{default:a((()=>[r(y,{color:"",variant:"text",onClick:e[0]||(e[0]=t=>j.closeDialog())},{default:a((()=>[m("Close ")])),_:1}),r(y,{variant:"elevated",color:"red-darken-1",onClick:e[1]||(e[1]=t=>j.deleteTrans())},{default:a((()=>[m(p(c.confirm),1)])),_:1})])),_:1})],64)}]]);export{d as default};