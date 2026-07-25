(function(global){
  async function request(baseUrl,action,{method='GET',params={},body}={}){
    const url=new URL(baseUrl,location.href);
    url.searchParams.set('action',action);
    Object.entries(params).forEach(([key,value])=>url.searchParams.set(key,value));
    try{
      const response=await fetch(url,{method,credentials:'include',headers:body?{'Content-Type':'application/json'}:{},body:body?JSON.stringify(body):undefined});
      const payload=await response.json().catch(()=>({error:{message:`服务器返回 ${response.status}`}}));
      if(!response.ok)return {data:null,error:payload.error||{message:`请求失败（${response.status}）`}};
      return {data:payload.data??null,error:null};
    }catch(error){
      return {data:null,error:{message:error?.message||'无法连接 MySQL API'}};
    }
  }

  class QueryBuilder{
    constructor(baseUrl,table,mode='select'){
      this.baseUrl=baseUrl;
      this.table=table;
      this.mode=mode;
      this.filters={};
      this.orderField='';
      this.orderAscending=true;
    }
    select(){this.mode='select';return this}
    delete(){this.mode='delete';return this}
    eq(field,value){this.filters[field]=value;return this}
    order(field,options={}){this.orderField=field;this.orderAscending=options.ascending!==false;return this.execute()}
    async execute(){
      if(this.mode==='delete')return request(this.baseUrl,'delete',{method:'POST',params:{table:this.table},body:{filters:this.filters}});
      const result=await request(this.baseUrl,'list',{params:{table:this.table}});
      if(!result.error&&this.orderField&&Array.isArray(result.data)){
        const direction=this.orderAscending?1:-1,field=this.orderField;
        result.data.sort((a,b)=>String(a[field]??'').localeCompare(String(b[field]??''),'zh-CN',{numeric:true})*direction);
      }
      return result;
    }
    then(resolve,reject){return this.execute().then(resolve,reject)}
  }

  function createClient(apiBaseUrl){
    const baseUrl=apiBaseUrl||'./api/index.php';
    return {
      provider:'mysql',
      auth:{
        async getSession(){
          const result=await request(baseUrl,'session');
          return {data:{session:result.data?.user?{user:result.data.user}:null},error:result.error};
        },
        async signInWithPassword({email,password}){
          const result=await request(baseUrl,'login',{method:'POST',body:{email,password}});
          return {data:{user:result.data?.user||null,session:result.data?.user?{user:result.data.user}:null},error:result.error};
        },
        async signUp({email,password}){
          const result=await request(baseUrl,'register',{method:'POST',body:{email,password}});
          return {data:{user:result.data?.user||null,session:result.data?.user?{user:result.data.user}:null},error:result.error};
        },
        async signOut(){return request(baseUrl,'logout',{method:'POST'})}
      },
      from(table){
        return {
          select(){return new QueryBuilder(baseUrl,table,'select')},
          upsert(rows){return request(baseUrl,'upsert',{method:'POST',params:{table},body:{rows:Array.isArray(rows)?rows:[rows]}})},
          delete(){return new QueryBuilder(baseUrl,table,'delete')}
        };
      }
    };
  }

  global.MySQLApi={createClient};
})(window);
