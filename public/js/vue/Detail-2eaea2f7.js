import e from"./Layout-69f33ced.js";import{r as l,o as t,h as s,f as a,a as o,w as n,t as u,d as i,F as d,l as c,c as r,k as m}from"./app-454c6d6d.js";const h={class:"mainAppWrapper d-flex flex-column"},b={class:"mainBarWrapper px-5 pt-3 flex-grow-0 flex-shrink-0"},p={class:"d-flex justify-space-between"},f={class:"--left d-flex"},v={class:"--left d-flex me-4"},g={class:"--listing"},A={class:"d-flex flex-1-0 flex-column w-0"},V=a("span",null,"Users",-1),j={class:"--objectTitle"},k={class:"--right"},C=a("span",{class:"--text"},"Basic",-1),D=a("span",{class:"--text"},"Permissions",-1),y={class:"mainContentWrapper detailContentWrapper d-flex flex-grow-1 flex-shrink-1 overflow-hidden"},_={class:"detailFormWrapper pa-5 flex-grow-1 overflow-auto"},w={class:"componentLayout-tabPanel"},x={class:"--formContent componentField-select"},O=a("label",null," Role ",-1),R={class:"--listing"},S=a("thead",null,[a("tr",null,[a("th",null," Documents "),a("th",null,"All"),a("th",null,"List"),a("th",null,"View"),a("th",null,"Save"),a("th",null,"Publish"),a("th",null," Unpublish "),a("th",null,"Delete"),a("th",null,"Create")])],-1),U=a("td",null,"All",-1),P=a("thead",null,[a("tr",null,[a("th",null,"Object"),a("th",null,"All"),a("th",null,"List"),a("th",null,"View"),a("th",null,"Save"),a("th",null,"Publish"),a("th",null," Unpublish "),a("th",null,"Delete"),a("th",null,"Create")])],-1),I=a("thead",null,[a("tr",null,[a("th",null,"Assets"),a("th",null,"All"),a("th",null,"List"),a("th",null,"View"),a("th",null,"Save"),a("th",null,"Publish"),a("th",null," Unpublish "),a("th",null,"Delete"),a("th",null,"Create")])],-1),T=a("td",null,"Assets",-1),L={data(){var e=[],l=[];return this.objectSetting.forEach((t=>{var s=this.user.splitArrPermission?this.user.splitArrPermission.object.filter((e=>e.includes(t.name+"Object"))):[];t.name,e=[t.name+"ObjectList",t.name+"ObjectView",t.name+"ObjectSave",t.name+"ObjectPublish",t.name+"ObjectUnPublish",t.name+"ObjectDelete",t.name+"ObjectCreate"],t.name,l.push({objArr:e,vmodelObjSelected:!1,vmodelObjArr:s,objName:t.name})})),console.log(this.objectSetting.objArr),{toast:m.useToast({position:"top-right",style:{zIndex:"200"}}),tab:null,tabPermission:null,column:null,inline:null,username:[e=>!!e||"Required.",e=>(e||"").length<=20||"Max 20 characters"],email:[e=>!!e||"Required.",e=>/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(e)||"Invalid e-mail."],name:[e=>!!e||"Required."],password:[e=>!!e||"Required.",e=>(e||"").length>=6||"Password must be at least 6 characters"],role:[e=>!!e||"Required."],documentRoles:["homeDocumentList","homeDocumentView","homeDocumentSave","homeDocumentPublish","homeDocumentUnPublish","homeDocumentDelete","homeDocumentCreate"],docRoleSelected:this.user.splitArrPermission.document,objectRoleSelected:[],assetsRoleSelected:this.user.splitArrPermission.assets,assetsRoles:["assetsList","assetsView","assetsSave","assetsPublish","assetsUnPublish","assetsDelete","assetsCreate"],selectAllDocument:!1,selectAllAssets:!1,objectOptions:l,toast:m.useToast(),showDeleteDialog:!1}},mounted(){this.checkIfAllAssetsChecked(),this.checkIfAllDocumentChecked(),this.objectSetting.forEach((e=>{this.checkIfAllObjectChecked(e)}))},methods:{showDeleteConfirmationDialog(e){this.itemToDelete=e,this.showDeleteDialog=!0},deleteItem(){const e=new FormData;e.append("id",this.itemToDelete),this.$inertia.post(route("user_delete"),e),this.showDeleteDialog=!1},closeDeleteDialog(){this.showDeleteDialog=!1,this.itemToDelete=null},save(e){this.objectOptions.forEach((e=>{this.objectRoleSelected=this.objectRoleSelected.concat(e.vmodelObjArr)})),this.user.permission=[...this.docRoleSelected,...this.objectRoleSelected,...this.assetsRoleSelected];const l=new FormData;Object.entries(this.user).forEach((([e,t])=>{null!=t&&l.append(e,t)})),fetch(route("user_detail",{id:this.user.id}),{method:"POST",body:l}).then((e=>{if(e.ok)return e.json();throw new Error("Lỗi xảy ra khi gửi yêu cầu.")})).then((e=>{e.warning?this.warningMess(e.warning):(this.successMess("Update user success!"),this.$inertia.get(route("user_detail",{id:this.user.id})))})).catch((e=>{console.error("Lỗi xảy ra khi gửi yêu cầu:",e)})).finally((()=>{this.loading=!1}))},toggleAllCheckboxesDocument(){this.docRoleSelected=this.selectAllDocument?this.documentRoles.slice():[]},checkIfAllDocumentChecked(){this.selectAllDocument=this.docRoleSelected.length==this.documentRoles.length},toggleAllCheckboxesAssets(){this.assetsRoleSelected=this.selectAllAssets?this.assetsRoles.slice():[]},checkIfAllAssetsChecked(){this.selectAllAssets=this.assetsRoleSelected.length==this.assetsRoles.length},checkIfAllObjectChecked(e,l){this.objectOptions.forEach((l=>{console.log(l),e==l.objName&&(l.vmodelObjSelected=l.vmodelObjArr.length==l.objArr.length)}))},toggleAllCheckboxesObject(e){this.objectOptions.forEach((l=>{e==l.objName&&(l.vmodelObjArr=l.vmodelObjSelected?l.objArr.slice():[])}))},backToListing(){this.$inertia.visit(route("user_listing"))},warningMess(e){e&&setTimeout((()=>{this.toast.open({message:e,type:"warning"})}),1e3)},successMess(e){e&&setTimeout((()=>{this.toast.open({message:e,type:"success"})}),1e3)}}},F=Object.assign(L,{layout:e},{__name:"Detail",props:{user:{permission:{}},roles:Array,flash:Object,objectSetting:Array},setup:e=>(m,L)=>{const F=l("v-icon"),q=l("v-breadcrumbs-item"),E=l("v-breadcrumbs"),N=l("v-btn"),W=l("v-toolbar-title"),M=l("v-toolbar"),z=l("v-card-text"),$=l("v-card-actions"),B=l("v-card"),Z=l("v-dialog"),G=l("v-tab"),H=l("v-tabs"),J=l("v-switch"),K=l("v-col"),Q=l("Input"),X=l("Select"),Y=l("v-row"),ee=l("v-window-item"),le=l("v-radio"),te=l("v-radio-group"),se=l("v-checkbox"),ae=l("Checkbox"),oe=l("v-table"),ne=l("v-window");return t(),s("div",h,[a("div",b,[a("div",p,[a("div",f,[a("div",v,[a("div",{class:"--objectIcon me-3",disabled:!1,onClick:L[0]||(L[0]=(...e)=>m.backToListing&&m.backToListing(...e))},[a("div",g,[o(F,{icon:"mdi-account-outline"}),o(F,{icon:"mdi-arrow-left"}),o(F,{icon:"mdi-account-outline"})])]),a("div",A,[o(E,{class:"breadcrumbWrapper pa-0"},{default:n((()=>[o(q,{class:"--itemBreadcrumb",disabled:!1,onClick:m.backToListing,title:"Users"},{default:n((()=>[V])),_:1},8,["onClick"])])),_:1}),a("h3",j,u(e.user.name),1)])])]),a("div",k,[o(N,{class:"btn-primary me-2",variant:"elevated","prepend-icon":"mdi-check",onClick:L[1]||(L[1]=e=>m.save())},{default:n((()=>[i(" Save ")])),_:1}),o(N,{variant:"elevated",class:"bg-red","prepend-icon":"mdi-delete-outline",onClick:L[2]||(L[2]=l=>m.showDeleteConfirmationDialog(e.user.id))},{default:n((()=>[i(" Delete ")])),_:1}),o(Z,{modelValue:m.showDeleteDialog,"onUpdate:modelValue":L[3]||(L[3]=e=>m.showDeleteDialog=e),"max-width":"300"},{default:n((()=>[o(B,null,{default:n((()=>[o(M,{color:"red-lighten-5",density:"compact"},{default:n((()=>[o(W,{text:"Delete",class:"text-red-darken-1"})])),_:1}),o(z,{class:"py-8"},{default:n((()=>[i(" Are you sure you want to delete? ")])),_:1}),o($,{class:"justify-end"},{default:n((()=>[o(N,{color:"",variant:"text",onClick:m.closeDeleteDialog},{default:n((()=>[i("Close ")])),_:1},8,["onClick"]),o(N,{variant:"elevated",color:"red-darken-1",onClick:m.deleteItem},{default:n((()=>[i(" Delete ")])),_:1},8,["onClick"])])),_:1})])),_:1})])),_:1},8,["modelValue"])])]),o(H,{modelValue:m.tab,"onUpdate:modelValue":L[4]||(L[4]=e=>m.tab=e),class:"mainTabPanel"},{default:n((()=>[o(G,{value:"information"},{default:n((()=>[C])),_:1}),o(G,{value:"permission"},{default:n((()=>[D])),_:1})])),_:1},8,["modelValue"])]),a("div",y,[a("div",_,[a("div",w,[a("form",{onSubmit:L[22]||(L[22]=(...e)=>m.submitForm&&m.submitForm(...e))},[o(ne,{modelValue:m.tab,"onUpdate:modelValue":L[21]||(L[21]=e=>m.tab=e)},{default:n((()=>[o(ee,{value:"information"},{default:n((()=>[o(Y,null,{default:n((()=>[o(K,{cols:"12"},{default:n((()=>[o(J,{label:"Active",color:"theme","false-value":0,"true-value":1,"hide-details":"",inset:"",modelValue:e.user.active,"onUpdate:modelValue":L[5]||(L[5]=l=>e.user.active=l),class:"--fieldRequired"},null,8,["modelValue"])])),_:1}),o(K,{cols:"6"},{default:n((()=>[o(Q,{name:"username",label:"Username",modelValue:e.user.username,"onUpdate:modelValue":L[6]||(L[6]=l=>e.user.username=l),class:"--fieldRequired"},null,8,["modelValue"]),o(Q,{name:"email",label:"Email",modelValue:e.user.email,"onUpdate:modelValue":L[7]||(L[7]=l=>e.user.email=l),class:"--fieldRequired"},null,8,["modelValue"]),o(Q,{name:"name",label:"Fullname",modelValue:e.user.name,"onUpdate:modelValue":L[8]||(L[8]=l=>e.user.name=l),class:"--fieldRequired"},null,8,["modelValue"]),a("div",x,[O,o(X,{name:"roles",items:e.roles,itemTitle:"name",itemValue:"id",modelValue:e.user.role,"onUpdate:modelValue":L[9]||(L[9]=l=>e.user.role=l),class:"--fieldRequired"},null,8,["items","modelValue"])])])),_:1})])),_:1})])),_:1}),o(ee,{value:"permission",class:"userPermissionWrapper"},{default:n((()=>[o(Y,null,{default:n((()=>[o(K,{cols:"12"},{default:n((()=>[o(te,{class:"--formContent componentField-radioGroup",modelValue:e.user.accessibleData,"onUpdate:modelValue":L[10]||(L[10]=l=>e.user.accessibleData=l),inline:"","hide-details":"",label:"Accessible"},{default:n((()=>[o(le,{label:"All",value:"all"}),o(le,{label:"My Data only",value:"personalDataOnly"})])),_:1},8,["modelValue"])])),_:1}),o(K,{cols:"12"},{default:n((()=>[a("div",R,[o(H,{modelValue:m.tabPermission,"onUpdate:modelValue":L[11]||(L[11]=e=>m.tabPermission=e),direction:"horizontal"},{default:n((()=>[o(G,{value:"documents"},{default:n((()=>[o(F,{start:""},{default:n((()=>[i(" mdi-file-document-outline ")])),_:1}),i(" Documents ")])),_:1}),o(G,{value:"object"},{default:n((()=>[o(F,{start:""},{default:n((()=>[i(" mdi-cube-outline ")])),_:1}),i(" Object ")])),_:1}),o(G,{value:"assets"},{default:n((()=>[o(F,{start:""},{default:n((()=>[i(" mdi-image-outline ")])),_:1}),i(" Assets ")])),_:1})])),_:1},8,["modelValue"]),o(ne,{modelValue:m.tabPermission,"onUpdate:modelValue":L[20]||(L[20]=e=>m.tabPermission=e)},{default:n((()=>[o(ee,{value:"documents",class:"pa-0"},{default:n((()=>[o(oe,{"fixed-header":""},{default:n((()=>[S,a("tbody",null,[a("tr",null,[U,a("td",null,[o(se,{modelValue:m.selectAllDocument,"onUpdate:modelValue":L[12]||(L[12]=e=>m.selectAllDocument=e),onChange:L[13]||(L[13]=e=>m.toggleAllCheckboxesDocument()),type:"multi","hide-details":"",class:"--formContent componentField-checkbox"},null,8,["modelValue"])]),(t(!0),s(d,null,c(m.documentRoles,(l=>(t(),s("td",null,[e.user.rolePermission.includes(l)?(t(),r(ae,{key:0,modelValue:m.docRoleSelected,"onUpdate:modelValue":L[14]||(L[14]=e=>m.docRoleSelected=e),label:"",value:l,type:"multi",color:"grey",onChange:m.checkIfAllDocumentChecked},null,8,["modelValue","value","onChange"])):(t(),r(ae,{key:1,modelValue:m.docRoleSelected,"onUpdate:modelValue":L[15]||(L[15]=e=>m.docRoleSelected=e),value:l,label:"",type:"multi",onChange:m.checkIfAllDocumentChecked},null,8,["modelValue","value","onChange"]))])))),256))])])])),_:1})])),_:1}),o(ee,{value:"object",class:"pa-0"},{default:n((()=>[o(oe,{"fixed-header":""},{default:n((()=>[P,a("tbody",null,[(t(!0),s(d,null,c(m.objectOptions,((l,n)=>(t(),s("tr",null,[a("td",null,u(l.objName),1),a("td",null,[o(se,{modelValue:l.vmodelObjSelected,"onUpdate:modelValue":e=>l.vmodelObjSelected=e,onChange:e=>m.toggleAllCheckboxesObject(l.objName),type:"multi","hide-details":"",class:"--formContent componentField-checkbox"},null,8,["modelValue","onUpdate:modelValue","onChange"])]),(t(!0),s(d,null,c(l.objArr,(a=>(t(),s("td",null,[e.user.rolePermission.includes(a)?(t(),r(ae,{key:0,modelValue:l.vmodelObjArr,"onUpdate:modelValue":e=>l.vmodelObjArr=e,label:"",value:a,type:"multi",color:"grey",onChange:e=>m.checkIfAllObjectChecked(l.objName,a)},null,8,["modelValue","onUpdate:modelValue","value","onChange"])):(t(),r(ae,{key:1,modelValue:l.vmodelObjArr,"onUpdate:modelValue":e=>l.vmodelObjArr=e,value:a,label:"",type:"multi",onChange:e=>m.checkIfAllObjectChecked(l.objName,a)},null,8,["modelValue","onUpdate:modelValue","value","onChange"]))])))),256))])))),256))])])),_:1})])),_:1}),o(ee,{value:"assets",class:"pa-0"},{default:n((()=>[o(oe,{"fixed-header":""},{default:n((()=>[I,a("tbody",null,[a("tr",null,[T,a("td",null,[o(se,{modelValue:m.selectAllAssets,"onUpdate:modelValue":L[16]||(L[16]=e=>m.selectAllAssets=e),onChange:L[17]||(L[17]=e=>m.toggleAllCheckboxesAssets()),type:"multi","hide-details":"",class:"--formContent componentField-checkbox"},null,8,["modelValue"])]),(t(!0),s(d,null,c(m.assetsRoles,(l=>(t(),s("td",null,[e.user.rolePermission.includes(l)?(t(),r(ae,{key:0,modelValue:m.assetsRoleSelected,"onUpdate:modelValue":L[18]||(L[18]=e=>m.assetsRoleSelected=e),label:"",value:l,type:"multi",color:"grey",onChange:m.checkIfAllAssetsChecked},null,8,["modelValue","value","onChange"])):(t(),r(ae,{key:1,modelValue:m.assetsRoleSelected,"onUpdate:modelValue":L[19]||(L[19]=e=>m.assetsRoleSelected=e),value:l,label:"",type:"multi",onChange:m.checkIfAllAssetsChecked},null,8,["modelValue","value","onChange"]))])))),256))])])])),_:1})])),_:1})])),_:1},8,["modelValue"])])])),_:1})])),_:1})])),_:1})])),_:1},8,["modelValue"])],32)])])])])}});export{F as default};