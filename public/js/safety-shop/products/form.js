(() => {
    'use strict';
    const form=document.getElementById('product-master-form'); if(!form)return;
    const barcode=document.getElementById('barcode'), message=document.getElementById('product-lookup-message'), method=document.getElementById('product-method');
    let last='';
    const fields=['barcode','sku','name','brand','size','unit','safety_standard','category_id','reorder_level','cost','price','is_active','notes'];
    const categorySearch=document.getElementById('category_search'), categoryId=document.getElementById('category_id'), categoryOptions=Array.from(document.querySelectorAll('#product-category-options option')), sku=document.getElementById('sku');
    let generatedSku='';
    const suggestSku=async()=>{if(!categoryId.value||form.dataset.originalProduct||(!generatedSku&&sku.value.trim()))return;try{const url=new URL(form.dataset.skuUrl,window.location.href);url.searchParams.set('company_id',document.getElementById('product-company-id').value);url.searchParams.set('category_id',categoryId.value);const response=await fetch(url,{headers:{Accept:'application/json'},credentials:'same-origin'});if(!response.ok)throw new Error();const data=await response.json();sku.value=data.sku;generatedSku=data.sku;}catch(error){generatedSku='';}};
    const syncCategory=()=>{const match=categoryOptions.find(option=>option.value.toLocaleLowerCase()===categorySearch.value.trim().toLocaleLowerCase());categoryId.value=match?match.dataset.id:'';if(generatedSku===sku.value||!sku.value.trim())suggestSku();};
    categorySearch.addEventListener('input',syncCategory); categorySearch.addEventListener('change',syncCategory);
    sku.addEventListener('input',()=>{if(sku.value!==generatedSku)generatedSku='';});
    document.querySelectorAll('[data-searchable-list]').forEach(input=>{
        const source=document.getElementById(input.getAttribute('list')); if(!source)return;
        const values=Array.from(source.options).map(option=>option.value).filter(Boolean);
        input.removeAttribute('list'); input.setAttribute('role','combobox'); input.setAttribute('aria-autocomplete','list'); input.setAttribute('aria-expanded','false');
        const wrap=document.createElement('div'); wrap.className='searchable-list-wrap'; input.parentNode.insertBefore(wrap,input); wrap.appendChild(input);
        const toggle=document.createElement('button'); toggle.type='button'; toggle.className='searchable-list-toggle'; toggle.setAttribute('aria-label','Show saved values'); toggle.textContent='⌄'; wrap.appendChild(toggle);
        const menu=document.createElement('div'); menu.className='searchable-list-menu'; menu.hidden=true; wrap.appendChild(menu);
        const close=()=>{menu.hidden=true;input.setAttribute('aria-expanded','false');};
        const render=(query='')=>{query=query.trim().toLocaleLowerCase();const matches=values.filter(value=>!query||value.toLocaleLowerCase().includes(query));menu.replaceChildren();matches.slice(0,100).forEach(value=>{const option=document.createElement('button');option.type='button';option.className='searchable-list-option';option.textContent=value;option.addEventListener('mousedown',event=>{event.preventDefault();input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}));input.dispatchEvent(new Event('change',{bubbles:true}));close();});menu.appendChild(option);});if(!matches.length){const empty=document.createElement('span');empty.className='searchable-list-empty';empty.textContent='No saved values. You can enter a new one.';menu.appendChild(empty);}menu.hidden=false;input.setAttribute('aria-expanded','true');};
        input.addEventListener('focus',()=>render()); input.addEventListener('click',()=>render()); input.addEventListener('input',()=>render(input.value)); toggle.addEventListener('click',()=>{if(menu.hidden){input.focus();render();}else close();});
        input.addEventListener('keydown',event=>{if(event.key==='Escape')close();}); document.addEventListener('mousedown',event=>{if(!wrap.contains(event.target))close();});
    });
    const show=text=>{message.textContent=text;message.hidden=false};
    async function lookup(){
        const code=barcode.value.trim(); if(!code||code===last)return; last=code; show('Checking barcode…');
        try{
            const url=new URL(form.dataset.barcodeUrl,window.location.href);url.searchParams.set('barcode',code);url.searchParams.set('company_id',document.getElementById('product-company-id').value);
            const response=await fetch(url,{headers:{Accept:'application/json'},credentials:'same-origin'});if(!response.ok)throw new Error('Barcode lookup failed.');
            const data=await response.json();
            if(!data.found){if(!form.dataset.originalProduct){form.action=form.dataset.createUrl;method.disabled=true;}show('New barcode. Complete the remaining fields to create the product.');return;}
            fields.forEach(name=>{const field=document.getElementById(name);if(field&&data.product[name]!==null&&data.product[name]!==undefined)field.value=data.product[name];});
            const category=categoryOptions.find(option=>option.dataset.id===String(data.product.category_id||''));categorySearch.value=category?category.value:'';
            form.action=data.update_url;method.disabled=false;method.value='PUT';form.dataset.originalProduct=data.id;
            show('Existing barcode found. Previous product details loaded; saving will update this product.');
        }catch(error){show(error.message);}
    }
    barcode.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();lookup();}});
    barcode.addEventListener('change',lookup);
    if(barcode.value)last=barcode.value.trim();
})();
