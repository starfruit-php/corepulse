import e from"./Layout-69f33ced.js";import{r as t,o,h as i,f as n,a,w as d,c as s,i as l,z as r,d as c,t as h,u as p,T as u,F as m,l as g,A as b,k as f}from"./app-454c6d6d.js";const T={class:"d-flex"},k={style:{width:"350px"}},w={class:"--sectionGroup"},v=n("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24"},[n("g",{fill:"none",stroke:"currentColor","stroke-linecap":"round","stroke-width":"1.5"},[n("path",{d:"m15.578 3.382l2 1.05c2.151 1.129 3.227 1.693 3.825 2.708C22 8.154 22 9.417 22 11.942v.117c0 2.524 0 3.787-.597 4.801c-.598 1.015-1.674 1.58-3.825 2.709l-2 1.049C13.822 21.539 12.944 22 12 22s-1.822-.46-3.578-1.382l-2-1.05c-2.151-1.129-3.227-1.693-3.825-2.708C2 15.846 2 14.583 2 12.06v-.117c0-2.525 0-3.788.597-4.802c.598-1.015 1.674-1.58 3.825-2.708l2-1.05C10.178 2.461 11.056 2 12 2s1.822.46 3.578 1.382Z"}),n("path",{d:"M21 7.5L12 12m0 0L3 7.5m9 4.5v9.5",opacity:".5"})])],-1),j=["data-node-id"],y=["data-node-id"],x={class:"mainAppWrapper d-flex flex-column"},C={class:"mainBarWrapper px-5 py-3 d-flex justify-space-between align-center flex-grow-0 flex-shrink-0"},N={class:"--left d-flex align-center"},D={class:"--formContent componentField-input"},O=n("label",null,"View Name",-1),A={key:0,class:"d-flex",style:{left:"0","flex-wrap":"wrap"}},L={key:1,class:"--right"},S={key:1,class:"mainContentWrapper px-5 py-4 d-flex flex-column flex-grow-1 flex-shrink-1 overflow-hidden"},_={class:"tableListingWrapper d-flex flex-column",style:{"max-height":"750px"}},V={data(){const e=route("cms_catalog");let t=window.localStorage.getItem(e);t=t?JSON.parse(t):[];let o="DataObject",i=new FormData;i.append("root",o);let n="";this.table&&(n=this.table);let a="";return this.tree&&(a=this.tree),{nodes:{},config:{},isLoading:!1,isLoadingTable:!1,dataListing:[],routerList:{router:route("tree_object_table")},headers:[],fields:[],limit:25,isTable:!1,nodeChecked:[],totalItems:0,root:o,formData:i,objectTable:n,objectTree:a,objectField:"",fieldsRelation:[],oldView:t,treeOpened:["ObjectTree"],dialog:!1,componentWidth:"500",componentModal:"",componentNode:{},viewName:"",toast:f.useToast({position:"top-right",style:{zIndex:"200"}}),checkTree:!1}},watch:{objectTree(e){this.$nextTick((()=>{this.nodeChecked=[],e&&this.handleMethodTree(),this.viewName=""}))},nodeChecked:{handler:function(e,t){this.$nextTick((()=>{this.objectTable&&this.objectField&&this.handleMethodListing()}))},deep:!0},objectTable(e){this.$nextTick((()=>{this.fieldsRelation=[],this.headers=[],this.fields=[],this.isTable=!1,e&&this.handleMethodListing(),this.viewName=""}))},objectField(e){this.$nextTick((()=>{this.objectTable&&this.handleMethodListing()}))},headers:{handler:function(e,t){this.$nextTick((()=>{if(e.length&&this.objectTable){const e=route("cms_catalog")+"/table="+this.objectTable;window.localStorage.setItem(e,JSON.stringify(this.headers))}}))},deep:!0},nodes:{handler:function(e,t){},deep:!0}},mounted(){this.$nextTick((()=>{this.objectTree&&(this.isLoading=!0,this.handleMethodTree()),this.objectTable&&(this.isLoadingTable=!0,this.handleMethodListing())}))},methods:{findClickedNode(e){const t=e.getAttribute("data-node-id");return t&&this.nodes[t]?this.nodes[t]:null},contextMenuAction(e){e.preventDefault(),this.findClickedNode(e.target)},nodeOpenedAction(e){},nodeClosedAction(e){},nodeToggleAction(e){},nodeBlurAction(e){},nodeEditAction(e){},nodeCheckedAction(e){this.nodeChecked.push(e)},nodeUncheckedAction(e){this.nodeChecked=this.nodeChecked.filter((t=>t.id!==e.id))},nodeDragstartAction(e){},nodeDragenterAction(e){},nodeDragleaveAction(e){},nodeDragendAction(e){},nodeOverAction(e){},nodeDropAction(e){},nodeFocusAction(e){this.$nextTick((()=>{this.isLoading=!0,this.updateChildren(e)}))},updateParent(e,t){let o=new FormData;e||(e=1),t||(t=1),o.append("root",this.root),o.append("id",e),o.append("parent",t),fetch(route("tree_update"),{method:"POST",body:o}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{this.isLoading=!1,e.status||this.getTree()})).catch((e=>{console.error(e)}))},updateChildren(e){const t=e.id,o=this.objectTree;let i=new FormData;i.append("parentId",t),i.append("className",o),fetch(route("tree_object_children"),{method:"POST",body:i}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{e.children?(this.nodes[t].children=e.children,this.nodes[t].state.opened=!0):this.nodes[t].state.indeterminate=!0,this.isLoading=!1})).catch((e=>{console.error(e)}))},getTree(){this.isLoading=!0;let e=new FormData;e.append("className",this.objectTree),fetch(route("tree_object_listing"),{method:"POST",body:e}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{this.$nextTick((()=>{this.isLoading=!1,this.nodes=e.nodes,this.config=e.config,this.treeOpened=["ObjectTree"]}))})).catch((e=>{console.error(e)}))},getHeader(e){let t=new FormData;t.append("classId",e),fetch(route("tree_object_field"),{method:"POST",body:t}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{this.$nextTick((()=>{const t=e,o=route("cms_catalog")+"/table="+this.objectTable,i=window.localStorage.getItem(o);this.headers=i?JSON.parse(i):t,this.fields=e}))})).catch((e=>{console.error(e)}))},getFieldRelation(e){let t=new FormData;t.append("classId",e),fetch(route("tree_object_field_relation"),{method:"POST",body:t}).then((e=>{if(e.ok)return e.json();throw new Error("Có lỗi khi gửi yêu cầu POST.")})).then((e=>{this.$nextTick((()=>{this.fieldsRelation=e}))})).catch((e=>{console.error(e)}))},filterNodes(){},updateTableListing(){this.$nextTick((()=>{this.isTable=!0,setTimeout((()=>{this.isLoadingTable=!1}),3e3)}))},removeChecked(){for(const e in this.nodes)this.nodes.hasOwnProperty(e)&&this.nodes[e].state&&(this.nodes[e].state.checked=!1);this.nodeChecked=[]},clearNodeChecked(e){this.nodes[e.id].state.checked=!1,this.nodeChecked=this.nodeChecked.filter((t=>t.id!==e.id))},saveView(){if(this.objectTable&&this.objectTree){const e=route("cms_catalog");let t=this.objectTree+" "+this.objectTable;this.viewName&&(t=this.viewName);let o={id:(new Date).getTime()/1e3,name:t,url:"?tree="+this.objectTree+"&table="+this.objectTable,tree:this.objectTree,table:this.objectTable};if(this.oldView){this.oldView.some((e=>e.url===o.url))?setTimeout((()=>{this.toast.open({message:"ObjectTree and ObjectTable is valid",type:"warning"})}),1e3):(this.oldView.push(o),window.localStorage.setItem(e,JSON.stringify(this.oldView)))}else window.localStorage.setItem(e,JSON.stringify({newView:o}));const i=window.localStorage.getItem(e);this.oldView=JSON.parse(i)}},handleMethodListing(){this.isLoadingTable=!0;const e=this.options.filter((e=>e.key==this.objectTable));if(e.length&&this.objectTable){const t=e[0].id;if(this.formData=new FormData,this.formData.append("classId",t),this.formData.append("className",this.objectTable),this.objectField&&this.nodeChecked){this.formData.append("fieldKey",this.objectField);for(const e of this.nodeChecked)this.formData.append("fieldData[]",e.id)}this.getHeader(t),this.getFieldRelation(t),this.isTable=!1,this.updateTableListing()}},handleMethodTree(){this.$nextTick((()=>{this.nodes={},this.config={},this.getTree()}))}}},F=Object.assign(V,{layout:e},{__name:"Layout",props:{options:[Object,Array],tree:String,table:String},setup:e=>(f,V)=>{const F=t("Select"),M=t("v-progress-linear"),I=t("v-list-item-title"),P=t("v-list-item"),$=t("v-icon"),U=t("v-tooltip"),E=t("v-list-group"),R=t("v-list"),J=t("v-text-field"),W=t("v-btn"),B=t("v-chip"),z=t("CustomField"),G=t("DataTableServer"),H=t("v-card"),K=t("v-dialog");return o(),i("div",T,[n("div",k,[a(F,{items:e.options,itemValue:"key",modelValue:f.objectTree,"onUpdate:modelValue":V[0]||(V[0]=e=>f.objectTree=e),label:"Select object",chips:!0},null,8,["items","modelValue"]),a(R,{opened:f.treeOpened,"onUpdate:opened":V[16]||(V[16]=e=>f.treeOpened=e),class:"",nav:"",density:"compact"},{default:d((()=>[n("div",w,[f.isLoading?(o(),s(M,{key:0,active:f.isLoading,indeterminate:""},null,8,["active"])):l("",!0),f.objectTree?(o(),s(E,{key:1,class:"itemGroupMenu",value:"ObjectTree"},{activator:d((({props:e})=>[a(P,r({class:"itemMainMenu"},e),{prepend:d((()=>[v])),default:d((()=>[a(I,null,{default:d((()=>[c(h(f.objectTree),1)])),_:1})])),_:2},1040)])),default:d((()=>[a(p(u),{config:f.config,nodes:f.nodes,onNodeOpened:V[1]||(V[1]=e=>f.nodeOpenedAction(e)),onNodeClosed:V[2]||(V[2]=e=>f.nodeClosedAction(e)),onNodeFocus:V[3]||(V[3]=e=>f.nodeFocusAction(e)),onNodeToggle:V[4]||(V[4]=e=>f.nodeToggleAction(e)),onNodeBlur:V[5]||(V[5]=e=>f.nodeBlurAction(e)),onNodeEdit:V[6]||(V[6]=e=>f.nodeEditAction(e)),onNodeChecked:V[7]||(V[7]=e=>f.nodeCheckedAction(e)),onNodeUnchecked:V[8]||(V[8]=e=>f.nodeUncheckedAction(e)),onNodeDragstart:V[9]||(V[9]=e=>f.nodeDragstartAction(e)),onNodeDragenter:V[10]||(V[10]=e=>f.nodeDragenterAction(e)),onNodeDragleave:V[11]||(V[11]=e=>f.nodeDragleaveAction(e)),onNodeDragend:V[12]||(V[12]=e=>f.nodeDragendAction(e)),onNodeOver:V[13]||(V[13]=e=>f.nodeOverAction(e)),onNodeDrop:V[14]||(V[14]=e=>f.nodeDropAction(e)),onContextmenu:V[15]||(V[15]=e=>f.contextMenuAction(e))},{"before-input":d((e=>[a($,null,{default:d((()=>[c(h(e.node.icon),1)])),_:2},1024)])),"after-input":d((e=>[e.node.published?(o(),i("span",{key:0,class:"data-node-id","data-node-id":e.node.id},h(e.node.key),9,j)):(o(),i("span",{key:1,class:"data-node-id","data-node-id":e.node.id},[n("del",null,h(e.node.key),1)],8,y)),a(U,{activator:"parent",location:"end"},{default:d((()=>[n("strong",null,"ID: "+h(e.node.id),1),c(" | Type: "+h(e.node.type)+" "+h("folder"!=e.node.type?e.node.className:""),1)])),_:2},1024)])),_:1},8,["config","nodes"])])),_:1})):l("",!0)])])),_:1},8,["opened"])]),n("div",x,[n("div",C,[n("div",N,[a(F,{items:e.options,itemValue:"key",modelValue:f.objectTable,"onUpdate:modelValue":V[17]||(V[17]=e=>f.objectTable=e),label:"Select object",chips:!0},null,8,["items","modelValue"]),f.fieldsRelation.length?(o(),s(F,{key:0,items:f.fieldsRelation,modelValue:f.objectField,"onUpdate:modelValue":V[18]||(V[18]=e=>f.objectField=e),label:"Select Field Relation",chips:!0},null,8,["items","modelValue"])):l("",!0),n("div",D,[O,a(J,{variant:"outlined",density:"compact","bg-color":"white",modelValue:f.viewName,"onUpdate:modelValue":V[19]||(V[19]=e=>f.viewName=e)},null,8,["modelValue"])]),a(W,{class:"text-none text-subtitle-1",color:"#5865f2",size:"small",variant:"elevated",onClick:V[20]||(V[20]=e=>f.saveView())},{default:d((()=>[c(" Save View ")])),_:1})]),f.nodeChecked.length&&f.objectField?(o(),i("div",A,[(o(!0),i(m,null,g(f.nodeChecked,((e,t)=>(o(),s(B,{key:t,value:e,closable:"",color:"primary","prepend-icon":"mdi-label",variant:"outlined","onClick:close":t=>f.clearNodeChecked(e)},{default:d((()=>[c(h(e.key),1)])),_:2},1032,["value","onClick:close"])))),128))])):l("",!0),f.fields.length?(o(),i("div",L,[a(z,{headers:f.headers,list:f.fields,"onUpdate:headers":V[21]||(V[21]=e=>f.headers=e)},null,8,["headers","list"])])):l("",!0)]),f.isLoadingTable?(o(),s(M,{key:0,active:f.isLoadingTable,indeterminate:""},null,8,["active"])):l("",!0),f.isTable&&f.fields.length?(o(),i("div",S,[n("div",_,[a(G,{listing:f.dataListing,router:f.routerList,"total-items":f.totalItems,headers:f.headers,limit:f.limit,fetchData:!0,noFilter:!0,formData:f.formData},null,8,["listing","router","total-items","headers","limit","formData"])])])):l("",!0)]),a(K,{modelValue:f.dialog,"onUpdate:modelValue":V[23]||(V[23]=e=>f.dialog=e),width:f.componentWidth},{default:d((()=>[a(H,null,{default:d((()=>[(o(),s(b(f.componentModal),{node:f.componentNode,"onUpdate:dialog":V[22]||(V[22]=e=>f.dialog=e)},null,40,["node"]))])),_:1})])),_:1},8,["modelValue","width"])])}});export{F as default};