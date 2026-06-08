// HNT.rocks uses one locked app palette.
// Do not switch Socialite into its template dark/light modes based on browser or OS preference.
(function () {
    try {
        window.localStorage.setItem('theme', 'hnt');
    } catch (error) {}

    document.documentElement.classList.remove('dark');
    document.documentElement.dataset.hntTheme = 'locked';
})();

// add post upload image 
const addPostUrl = document.getElementById('addPostUrl');
const addPostImage = document.getElementById('addPostImage');
if (addPostUrl && addPostImage) {
    addPostUrl.addEventListener('change', function(){
        if (this.files[0] ) {
            var picture = new FileReader();
            picture.readAsDataURL(this.files[0]);
            picture.addEventListener('load', function(event) {
                addPostImage.setAttribute('src', event.target.result);
                addPostImage.style.display = 'block';
            });
        }
    });
}


// Create Status upload image 
const createStatusUrl = document.getElementById('createStatusUrl');
const createStatusImage = document.getElementById('createStatusImage');
if (createStatusUrl && createStatusImage) {
    createStatusUrl.addEventListener('change', function(){
        if (this.files[0] ) {
            var picture = new FileReader();
            picture.readAsDataURL(this.files[0]);
            picture.addEventListener('load', function(event) {
                createStatusImage.setAttribute('src', event.target.result);
                createStatusImage.style.display = 'block';
            });
        }
    });
}


// create product upload image
const createProductUrl = document.getElementById('createProductUrl');
const createProductImage = document.getElementById('createProductImage');
if (createProductUrl && createProductImage) {
    createProductUrl.addEventListener('change', function(){
        if (this.files[0] ) {
            var picture = new FileReader();
            picture.readAsDataURL(this.files[0]);
            picture.addEventListener('load', function(event) {
                createProductImage.setAttribute('src', event.target.result);
                createProductImage.style.display = 'block';
            });
        }
    });
}







    