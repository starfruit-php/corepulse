import e from"./Layout-9a29d640.js";import{r as t,o as l,h as i,f as a,a as o,w as s,t as c,d as n,S as r,x as d,M as u,y as p,U as h}from"./app-b548be80.js";const b={class:"mainAppWrapper d-flex flex-column"},m={class:"mainBarWrapper px-5 py-3 d-flex justify-space-between align-center flex-grow-0 flex-shrink-0"},f={class:"--left d-flex align-center"},j={class:"d-flex flex-column me-4"},y={class:"--objectTitle"},v={class:"--right"},g=a("div",{class:"utilityBarWrapper flex-grow-0 flex-shrink-0"},null,-1),O={class:"mainContentWrapper px-5 py-4 d-flex flex-column flex-grow-1 flex-shrink-1 overflow-hidden"},x={class:"tableListingWrapper d-flex flex-column"},_={name:"Object",data(){var e,t=window.location.href;let l=window.localStorage.getItem(t),i=[{title:"Title",key:"key",sortable:!1,removable:!0,handleEdit:!0,handleClick:!0,clickUrl:"vuetify_object_detail",url:{path:"object_listing_edit"},searchType:"Input",component:"ModalEdit",type:"key"},{title:"Status",key:"published",removable:!0,searchType:"Select",handleEdit:!0,url:{path:"object_listing_edit"},component:"ModalEdit",type:"published",options:[{value:"Published",key:"Published"},{value:"Unpublished",key:"Unpublished"}]},{title:"Last Update",key:"modificationDate",removable:!0,searchType:"nosearch"}];this.fields.map((e=>{i.push({title:""!=e.title?e.title:e.key,type:e.type,key:e.key,removable:!1,searchType:e.searchType,options:e.options,url:e.url,component:e.component,handleEdit:e.handleEdit})})),l?(l=JSON.parse(l),l.length!=i.length&&(l=i,window.localStorage.setItem(t,JSON.stringify(l)))):(l=i,window.localStorage.setItem(t,JSON.stringify(l)));var a=[];l.map((e=>{e.removable&&a.push(e)}));var o=[{title:"Sửa",icon:"mdi-pencil-outline",url:"vuetify_object_detail",post:!1,reload:!1},{title:"Xóa",icon:"mdi-delete-outline",url:{path:"object_delete",data:{object:this.classObject.name}},component:d,post:!0,reload:!1}],s=[{title:"Published",icon:"mdi-tray-arrow-up",url:{path:"object_selected_edit",data:{published:"Published"},content:{title:"Published",content:"Are you sure you want to publish all selected objects",confirm:"Published"}},component:u,post:!0,reload:!1},{title:"Unpublished",icon:"mdi-tray-arrow-down",url:{path:"object_selected_edit",data:{published:"Unpublished"},content:{title:"Unpublished",content:"Are you sure you want to unpublish all selected objects",confirm:"UnPublished"}},component:u,post:!0,reload:!1},{title:"Delete",icon:"mdi-delete-outline",url:{path:"object_delete",data:{object:this.classObject.name},content:{title:"Delete",content:"Are you sure you want to delete all selected objects",confirm:"Delete"}},component:u,post:!0,reload:!1}],c=null;return this.classObject&&(c={object:this.classObject.id}),{ModalDelete:p(d),ModalEdit:p(h),ModalSelected:p(u),ModalCreate:p(r),createObject:!1,fav:!0,menu:!1,message:!1,hints:!0,enabled:!0,headers:a,list:l,routerList:{router:route("vuetify_object",c)},action:o,selectedAction:s,title:null==(e=this.classObject)?void 0:e.title}},mounted(){this.checkTitle()},methods:{checkTitle(){if(this.objectSetting){const e=this.objectSetting.find((e=>e.id===this.classObject.id));e&&(this.title=e.title)}},createAction:(e,t,l,i=!1,a=!0,o=!0,s=!0)=>({title:e,icon:t,url:l,component:i,multiple:a,post:o,reload:s}),exportExcel(){let e=new FormData;if(this.listing){for(var[t,l]of Object.entries(this.listing))e.append("data[]",JSON.stringify(l));var i=this.headers;e.append("fields",JSON.stringify(i)),this.loading=!0,fetch(route("vuetify_export_excel"),{method:"POST",body:e}).then((e=>e.blob())).then((e=>{const t=URL.createObjectURL(e);console.log(t);const l=document.createElement("a");l.href=t,l.download="object-listing.xlsx",l.click(),URL.revokeObjectURL(t)})).catch((e=>{console.log(e)})).finally((()=>{this.loading=!1}))}}}},k=Object.assign(_,{layout:e},{props:{listing:Object,fields:{type:Object,default:[]},search:{type:Object,default:{}},classObject:Object,totalItems:Number,chips:[Object,Array],chipsColor:[Object,Array],warning:[Object,Array,String],noOrder:[Object,Array],multiSelect:[Object,Array],objectSetting:[Object,Array]},setup:e=>(d,u)=>{const p=t("v-icon"),h=t("v-breadcrumbs-item"),_=t("v-breadcrumbs"),k=t("v-btn"),w=t("v-menu"),S=t("v-dialog"),U=t("CustomField"),A=t("DataTableServer");return l(),i("div",b,[a("div",m,[a("div",f,[o(p,{icon:"mdi-cube-outline",class:"--objectIcon me-3"}),a("div",j,[o(_,{class:"breadcrumbWrapper pa-0"},{default:s((()=>[o(h,{class:"--itemBreadcrumb"},{default:s((()=>[a("small",null,c(d.$t("Object")),1)])),_:1})])),_:1}),a("h3",y,c(d.title),1)])]),a("div",v,[o(w,{modelValue:d.menu,"onUpdate:modelValue":u[1]||(u[1]=e=>d.menu=e),"close-on-content-click":!1},{activator:s((({props:e})=>[o(k,{onClick:u[0]||(u[0]=e=>d.createObject=!0),class:"btn-primary me-2",variant:"elevated"},{default:s((()=>[o(p,{left:"",class:"me-2"},{default:s((()=>[n("mdi-plus")])),_:1}),n(" "+c(d.$t("Create")),1)])),_:1})])),_:1},8,["modelValue"]),o(S,{modelValue:d.createObject,"onUpdate:modelValue":u[2]||(u[2]=e=>d.createObject=e),width:"420"},{default:s((()=>[o(r,{object:d.title,url:{path:"object_check_key"},urlReturn:{path:"vuetify_object_detail"}},null,8,["object"])])),_:1},8,["modelValue"]),o(k,{variant:"text",class:"","prepend-icon":"mdi-microsoft-excel",onClick:d.exportExcel},{default:s((()=>[n(c(d.$t("Export")),1)])),_:1},8,["onClick"]),o(U,{headers:d.headers,list:d.list,"onUpdate:headers":u[3]||(u[3]=e=>d.headers=e)},null,8,["headers","list"])])]),g,a("div",O,[a("div",x,[o(A,{listing:e.listing,router:d.routerList,totalItems:e.totalItems,headers:d.headers,action:d.action,chips:e.chips,chipsColor:e.chipsColor,selectedAction:d.selectedAction,noOrder:e.noOrder,multiSelect:e.multiSelect},null,8,["listing","router","totalItems","headers","action","chips","chipsColor","selectedAction","noOrder","multiSelect"])])])])}});export{k as default};