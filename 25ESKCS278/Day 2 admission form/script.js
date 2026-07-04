const btn = document.querySelector("#Btn");
btn.addEventListener("click" ,function(){


    let count=parseInt(btn.dataset.count || 0);
    count++;
    btn.dataset.count = count;
    document.querySelector("#display").textContent=`click:{count}`;
});
