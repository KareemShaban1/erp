const popup = document.querySelector('.popup');
const close = document.querySelector('.close');

function colsePopup(time=3000){
    setTimeout(function(){
        popup.style.display = 'none'
    },time)
}  
close.addEventListener('click',()=>{
    popup.style.display = 'none'
})