import e from"./Layout-808b9c88.js";import{T as t}from"./vue3-treeview-e35ab4eb.js";import{d as o}from"./vue-toast-notification-f2a3de7a.js";import{am as i,o as n,c as a,a as d,k as s,q as l,C as r,E as c,A as h,N as p,M as u,z as m,K as g,p as b,D as f}from"./@vue-9ee83fb6.js";import"./@vueuse-3c3b27e0.js";import"./unidecode-5a54da0f.js";import"./@ckeditor-fa9b3055.js";import"./vue-abe8eda6.js";const T={class:"d-flex"},k={style:{width:"350px"}},v={class:"--sectionGroup"},w=d("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24"},[d("g",{fill:"none",stroke:"currentColor","stroke-linecap":"round","stroke-width":"1.5"},[d("path",{d:"m15.578 3.382l2 1.05c2.151 1.129 3.227 1.693 3.825 2.708C22 8.154 22 9.417 22 11.942v.117c0 2.524 0 3.787-.597 4.801c-.598 1.015-1.674 1.58-3.825 2.709l-2 1.049C13.822 21.539 12.944 22 12 22s-1.822-.46-3.578-1.382l-2-1.05c-2.151-1.129-3.227-1.693-3.825-2.708C2 15.846 2 14.583 2 12.06v-.117c0-2.525 0-3.788.597-4.802c.598-1.015 1.674-1.58 3.825-2.708l2-1.05C10.178 2.461 11.056 2 12 2s1.822.46 3.578 1.382Z"}),d("path",{d:"M21 7.5L12 12m0 0L3 7.5m9 4.5v9.5",opacity:".5"})])],-1),j=["data-node-id"],y=["data-node-id"],C={class:"mainAppWrapper d-flex flex-column"},x={class:"mainBarWrapper px-5 py-3 d-flex justify-space-between align-center flex-grow-0 flex-shrink-0"},N={class:"--left d-flex align-center"},D={class:"--formContent componentField-input"},O=d("label",null,"View Name",-1),A={key:0,class:"d-flex",style:{left:"0","flex-wrap":"wrap"}},L={key:1,class:"--right"},S={key:1,class:"mainContentWrapper px-5 py-4 d-flex flex-column flex-grow-1 flex-shrink-1 overflow-hidden"},V={class:"tableListingWrapper d-flex flex-column",style:{"max-height":"750px"}},_={data(){const e=route("cms_catalog");let t=window.localStorage.getItem(e);t=t?JSON.parse(t):[];let i="DataObject",n=new FormData;n.append("root",i);let a="";this.table&&(a=this.table);let d="";return this.tree&&(d=this.tree),{nodes:{},config:{},isLoading:!1,isLoadingTable:!1,dataListing:[],routerList:{router:route("tree_object_table")},headers:[],fields:[],limit:25,isTable:!1,nodeChecked:[],totalItems:0,root:i,formData:n,objectTable:a,objectTree:d,objectField:"",fieldsRelation:[],oldView:t,treeOpened:["ObjectTree"],dialog:!1,componentWidth:"500",componentModal:"",componentNode:{},viewName:"",toast:o.useToast({position:"top-right",style:{zIndex:"200"}}),checkTree:!1}},watch:{objectTree(e){this.$nextTick((()=>{this.nodeChecked=[],e&&this.handleMethodTree(),this.viewName=""}))},nodeChecked:{handler:function(e,t){this.$nextTick((()=>{this.objectTable&&this.objectField&&this.handleMethodListing()}))},deep:!0},objectTable(e){this.$nextTick((()=>{this.fieldsRelation=[],this.headers=[],this.fields=[],this.isTable=!1,e&&this.handleMethodListing(),this.viewName=""}))},objectField(e){this.$nextTick((()=>{this.objectTable&&this.handleMethodListing()}))},headers:{handler:function(e,t){this.$nextTick((()=>{if(e.length&&this.objectTable){const e=route("cms_catalog")+"/table="+this.objectTable;window.localStorage.setItem(e,JSON.stringify(this.headers))}}))},deep:!0},nodes:{handler:function(e,t){},deep:!0}},mounted(){this.$nextTick((()=>{this.objectTree&&(this.isLoading=!0,this.handleMethodTree()),this.objectTable&&(this.isLoadingTable=!0,this.handleMethodListing())}))},methods:{findClickedNode(e){const t=e.getAttribute("data-node-id");return t&&this.nodes[t]?this.nodes[t]:null},contextMenuAction(e){e.preventDefault(),this.findClickedNode(e.target)},nodeOpenedAction(e){},nodeClosedAction(e){},nodeToggleAction(e){},nodeBlurAction(e){},nodeEditAction(e){},nodeCheckedAction(e){this.nodeChecked.push(e)},nodeUncheckedAction(e){this.nodeChecked=this.nodeChecked.filter((t=>t.id!==e.id))},nodeDragstartAction(e){},nodeDragenterAction(e){},nodeDragleaveAction(e){},nodeDragendAction(e){},nodeOverAction(e){},nodeDropAction(e){},nodeFocusAction(e){this.$nextTick((()=>{this.isLoading=!0,this.updateChildren(e)}))},updateParent(e,t){let o=new FormData;e||(e=1),t||(t=1),o.append("root",this.root),o.append("id",e),o.append("parent",t),fetch(route("tree_update"),{method:"POST",body:o}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{this.isLoading=!1,e.status||this.getTree()})).catch((e=>{console.error(e)}))},updateChildren(e){const t=e.id,o=this.objectTree;let i=new FormData;i.append("parentId",t),i.append("className",o),fetch(route("tree_object_children"),{method:"POST",body:i}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{e.children?(this.nodes[t].children=e.children,this.nodes[t].state.opened=!0):this.nodes[t].state.indeterminate=!0,this.isLoading=!1})).catch((e=>{console.error(e)}))},getTree(){this.isLoading=!0;let e=new FormData;e.append("className",this.objectTree),fetch(route("tree_object_listing"),{method:"POST",body:e}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{this.$nextTick((()=>{this.isLoading=!1,this.nodes=e.nodes,this.config=e.config,this.treeOpened=["ObjectTree"]}))})).catch((e=>{console.error(e)}))},getHeader(e){let t=new FormData;t.append("classId",e),fetch(route("tree_object_field"),{method:"POST",body:t}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{this.$nextTick((()=>{const t=e,o=route("cms_catalog")+"/table="+this.objectTable,i=window.localStorage.getItem(o);this.headers=i?JSON.parse(i):t,this.fields=e}))})).catch((e=>{console.error(e)}))},getFieldRelation(e){let t=new FormData;t.append("classId",e),fetch(route("tree_object_field_relation"),{method:"POST",body:t}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{this.$nextTick((()=>{this.fieldsRelation=e}))})).catch((e=>{console.error(e)}))},filterNodes(){},updateTableListing(){this.$nextTick((()=>{this.isTable=!0,setTimeout((()=>{this.isLoadingTable=!1}),3e3)}))},removeChecked(){for(const e in this.nodes)this.nodes.hasOwnProperty(e)&&this.nodes[e].state&&(this.nodes[e].state.checked=!1);this.nodeChecked=[]},clearNodeChecked(e){this.nodes[e.id].state.checked=!1,this.nodeChecked=this.nodeChecked.filter((t=>t.id!==e.id))},saveView(){if(this.objectTable&&this.objectTree){const e=route("cms_catalog");let t=this.objectTree+" "+this.objectTable;this.viewName&&(t=this.viewName);let o={id:(new Date).getTime()/1e3,name:t,url:"?tree="+this.objectTree+"&table="+this.objectTable,tree:this.objectTree,table:this.objectTable};if(this.oldView){this.oldView.some((e=>e.url===o.url))?setTimeout((()=>{this.toast.open({message:"ObjectTree and ObjectTable is valid",type:"warning"})}),1e3):(this.oldView.push(o),window.localStorage.setItem(e,JSON.stringify(this.oldView)))}else window.localStorage.setItem(e,JSON.stringify({newView:o}));const i=window.localStorage.getItem(e);this.oldView=JSON.parse(i)}},handleMethodListing(){this.isLoadingTable=!0;const e=this.options.filter((e=>e.key==this.objectTable));if(e.length&&this.objectTable){const t=e[0].id;if(this.formData=new FormData,this.formData.append("classId",t),this.formData.append("className",this.objectTable),this.objectField&&this.nodeChecked){this.formData.append("fieldKey",this.objectField);for(const e of this.nodeChecked)this.formData.append("fieldData[]",e.id)}this.getHeader(t),this.getFieldRelation(t),this.isTable=!1,this.updateTableListing()}},handleMethodTree(){this.$nextTick((()=>{this.nodes={},this.config={},this.getTree()}))}}},F=Object.assign(_,{layout:e},{__name:"Layout",props:{options:[Object,Array],tree:String,table:String},setup:e=>(o,_)=>{const F=i("Select"),M=i("v-progress-linear"),I=i("v-list-item-title"),P=i("v-list-item"),$=i("v-icon"),U=i("v-tooltip"),E=i("v-list-group"),R=i("v-list"),J=i("v-text-field"),W=i("v-btn"),B=i("v-chip"),z=i("CustomField"),G=i("DataTableServer"),H=i("v-card"),K=i("v-dialog");return n(),a("div",T,[d("div",k,[s(F,{items:e.options,itemValue:"key",modelValue:o.objectTree,"onUpdate:modelValue":_[0]||(_[0]=e=>o.objectTree=e),label:"Select object",chips:!0},null,8,["items","modelValue"]),s(R,{opened:o.treeOpened,"onUpdate:opened":_[16]||(_[16]=e=>o.treeOpened=e),class:"",nav:"",density:"compact"},{default:l((()=>[d("div",v,[o.isLoading?(n(),r(M,{key:0,active:o.isLoading,indeterminate:""},null,8,["active"])):c("",!0),o.objectTree?(n(),r(E,{key:1,class:"itemGroupMenu",value:"ObjectTree"},{activator:l((({props:e})=>[s(P,h({class:"itemMainMenu"},e),{prepend:l((()=>[w])),default:l((()=>[s(I,null,{default:l((()=>[p(u(o.objectTree),1)])),_:1})])),_:2},1040)])),default:l((()=>[s(m(t),{config:o.config,nodes:o.nodes,onNodeOpened:_[1]||(_[1]=e=>o.nodeOpenedAction(e)),onNodeClosed:_[2]||(_[2]=e=>o.nodeClosedAction(e)),onNodeFocus:_[3]||(_[3]=e=>o.nodeFocusAction(e)),onNodeToggle:_[4]||(_[4]=e=>o.nodeToggleAction(e)),onNodeBlur:_[5]||(_[5]=e=>o.nodeBlurAction(e)),onNodeEdit:_[6]||(_[6]=e=>o.nodeEditAction(e)),onNodeChecked:_[7]||(_[7]=e=>o.nodeCheckedAction(e)),onNodeUnchecked:_[8]||(_[8]=e=>o.nodeUncheckedAction(e)),onNodeDragstart:_[9]||(_[9]=e=>o.nodeDragstartAction(e)),onNodeDragenter:_[10]||(_[10]=e=>o.nodeDragenterAction(e)),onNodeDragleave:_[11]||(_[11]=e=>o.nodeDragleaveAction(e)),onNodeDragend:_[12]||(_[12]=e=>o.nodeDragendAction(e)),onNodeOver:_[13]||(_[13]=e=>o.nodeOverAction(e)),onNodeDrop:_[14]||(_[14]=e=>o.nodeDropAction(e)),onContextmenu:_[15]||(_[15]=e=>o.contextMenuAction(e))},{"before-input":l((e=>[s($,null,{default:l((()=>[p(u(e.node.icon),1)])),_:2},1024)])),"after-input":l((e=>[e.node.published?(n(),a("span",{key:0,class:"data-node-id","data-node-id":e.node.id},u(e.node.key),9,j)):(n(),a("span",{key:1,class:"data-node-id","data-node-id":e.node.id},[d("del",null,u(e.node.key),1)],8,y)),s(U,{activator:"parent",location:"end"},{default:l((()=>[d("strong",null,"ID: "+u(e.node.id),1),p(" | Type: "+u(e.node.type)+" "+u("folder"!=e.node.type?e.node.className:""),1)])),_:2},1024)])),_:1},8,["config","nodes"])])),_:1})):c("",!0)])])),_:1},8,["opened"])]),d("div",C,[d("div",x,[d("div",N,[s(F,{items:e.options,itemValue:"key",modelValue:o.objectTable,"onUpdate:modelValue":_[17]||(_[17]=e=>o.objectTable=e),label:"Select object",chips:!0},null,8,["items","modelValue"]),o.fieldsRelation.length?(n(),r(F,{key:0,items:o.fieldsRelation,modelValue:o.objectField,"onUpdate:modelValue":_[18]||(_[18]=e=>o.objectField=e),label:"Select Field Relation",chips:!0},null,8,["items","modelValue"])):c("",!0),d("div",D,[O,s(J,{variant:"outlined",density:"compact","bg-color":"white",modelValue:o.viewName,"onUpdate:modelValue":_[19]||(_[19]=e=>o.viewName=e)},null,8,["modelValue"])]),s(W,{class:"text-none text-subtitle-1",color:"#5865f2",size:"small",variant:"elevated",onClick:_[20]||(_[20]=e=>o.saveView())},{default:l((()=>[p(" Save View ")])),_:1})]),o.nodeChecked.length&&o.objectField?(n(),a("div",A,[(n(!0),a(g,null,b(o.nodeChecked,((e,t)=>(n(),r(B,{key:t,value:e,closable:"",color:"primary","prepend-icon":"mdi-label",variant:"outlined","onClick:close":t=>o.clearNodeChecked(e)},{default:l((()=>[p(u(e.key),1)])),_:2},1032,["value","onClick:close"])))),128))])):c("",!0),o.fields.length?(n(),a("div",L,[s(z,{headers:o.headers,list:o.fields,"onUpdate:headers":_[21]||(_[21]=e=>o.headers=e)},null,8,["headers","list"])])):c("",!0)]),o.isLoadingTable?(n(),r(M,{key:0,active:o.isLoadingTable,indeterminate:""},null,8,["active"])):c("",!0),o.isTable&&o.fields.length?(n(),a("div",S,[d("div",V,[s(G,{listing:o.dataListing,router:o.routerList,"total-items":o.totalItems,headers:o.headers,limit:o.limit,fetchData:!0,noFilter:!0,formData:o.formData},null,8,["listing","router","total-items","headers","limit","formData"])])])):c("",!0)]),s(K,{modelValue:o.dialog,"onUpdate:modelValue":_[23]||(_[23]=e=>o.dialog=e),width:o.componentWidth},{default:l((()=>[s(H,null,{default:l((()=>[(n(),r(f(o.componentModal),{node:o.componentNode,"onUpdate:dialog":_[22]||(_[22]=e=>o.dialog=e)},null,40,["node"]))])),_:1})])),_:1},8,["modelValue","width"])])}});export{F as default};