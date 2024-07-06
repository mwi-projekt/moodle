const params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams, prop) => searchParams.get(prop),
});
const view = params.view;

if(view == 12 || view == 14) {
    const selectStudiengang = document.querySelector(".inputStudiengang > select");
    
    const divStudienrichtungBwlIndustrie = document.getElementById(".divStudienrichtungBwlIndustrie");
    const selectStudienrichtungBwlIndustrie = document.getElementById(".divStudienrichtungBwlIndustrie > select");
    
    const divStudienrichtungElektrotechnik = document.getElementById(".divStudienrichtungElektrotechnik");
    const selectStudienrichtungElektrotechnik = document.getElementById(".divStudienrichtungElektrotechnik > select");
    
    const divStudienrichtungInformatik = document.getElementById(".divStudienrichtungInformatik");
    const selectStudienrichtungInformatik = document.getElementById(".divStudienrichtungInformatik > select");
    
    const divStudienrichtungMaschinenbau = document.getElementById(".divStudienrichtungMaschinenbau");
    const selectStudienrichtungMaschinenbau = document.getElementById(".divStudienrichtungMaschinenbau > select");
    
    const divStudienrichtungSicherheitswesen = document.getElementById(".divStudienrichtungSicherheitswesen");
    const selectStudienrichtungSicherheitswesen = document.getElementById(".divStudienrichtungSicherheitswesen > select");
    
    const divStudienrichtungSustainableScienceAndTechnology = document.getElementById(".divStudienrichtungSustainableScienceAndTechnology");
    const selectStudienrichtungSustainableScienceAndTechnology = document.getElementById(".divStudienrichtungSustainableScienceAndTechnology > select");
    
    const divStudienrichtungUnternehmertum = document.getElementById(".divStudienrichtungUnternehmertum");
    const selectStudienrichtungUnternehmertum = document.getElementById(".divStudienrichtungUnternehmertum > select");
    
    const divStudienrichtungWirtschaftsinformatik = document.getElementById(".divStudienrichtungWirtschaftsinformatik");
    const selectStudienrichtungWirtschaftsinformatik = document.getElementById(".divStudienrichtungWirtschaftsinformatik > select");
    
    const divStudienrichtungWirtschaftsingenieurwesen = document.getElementById(".divStudienrichtungWirtschaftsingenieurwesen");
    const selectStudienrichtungWirtschaftsingenieurwesen = document.getElementById(".selectStudienrichtungWirtschaftsingenieurwesen > select");
    
    const divStudienrichtung = document.getElementById(".divStudienrichtung");

    const saveButton = document.getElementById("id_submitbutton_save");

    let idStudienrichtung = -1;
    

    function doNotShowStudienrichtung() {
        divStudienrichtungBwlIndustrie.style.display = "none";    
        divStudienrichtungElektrotechnik.style.display = "none";    
        divStudienrichtungInformatik.style.display = "none";    
        divStudienrichtungMaschinenbau.style.display = "none";    
        divStudienrichtungSicherheitswesen.style.display = "none";    
        divStudienrichtungSustainableScienceAndTechnology.style.display = "none";    
        divStudienrichtungUnternehmertum.style.display = "none";    
        divStudienrichtungWirtschaftsinformatik.style.display = "none";    
        divStudienrichtungWirtschaftsingenieurwesen.style.display = "none";    
        divStudienrichtung.style.display = "none";
    }

    function showStudienrichtung(div) {
        div.style.display = "flex";
        divStudienrichtung.style.display = "block";
    }

    function deselectUnshownDivStudienrichtung(idShown) {
        if(idShown != 8) {
            selectStudienrichtungBwlIndustrie.selectedIndex = "0";
        }
        if(idShown != 10) {
            selectStudienrichtungElektrotechnik.selectedIndex = "0";
        }
        if(idShown != 11) {
            selectStudienrichtungInformatik.selectedIndex = "0";
        }
        if(idShown != 12) {
            selectStudienrichtungMaschinenbau.selectedIndex = "0";
        }
        if(idShown != 17) {
            selectStudienrichtungSicherheitswesen.selectedIndex = "0";
        }
        if(idShown != 18) {
            selectStudienrichtungSustainableScienceAndTechnology.selectedIndex = "0";
        }
        if(idShown != 19) {
            selectStudienrichtungUnternehmertum.selectedIndex = "0";
        }
        if(idShown != 20) {
            selectStudienrichtungWirtschaftsinformatik.selectedIndex = "0";
        }
        if(idShown != 21) {
            selectStudienrichtungWirtschaftsingenieurwesen.selectedIndex = "0";
        }
        divStudienrichtung.style.display = "none";
    }
    

    selectStudiengang.addEventListener("change", function() {
        doNotShowStudienrichtung();
        idStudienrichtung = this.selectedIndex;
        switch(this.selectedIndex) {
            case 8:
                showStudienrichtung(divStudienrichtungBwlIndustrie);
                break;
            case 10:
                showStudienrichtung(divStudienrichtungElektrotechnik);
                break;
            case 11:
                showStudienrichtung(divStudienrichtungInformatik);
                break;
            case 12:
                showStudienrichtung(divStudienrichtungMaschinenbau);
                break;
            case 17:
                showStudienrichtung(divStudienrichtungSicherheitswesen);
                break;
            case 18:
                showStudienrichtung(divStudienrichtungSustainableScienceAndTechnology);
                break;
            case 19:
                showStudienrichtung(divStudienrichtungUnternehmertum);
                break;
            case 20:
                showStudienrichtung(divStudienrichtungWirtschaftsinformatik);
                break;
            case 21:
                showStudienrichtung(divStudienrichtungWirtschaftsingenieurwesen);
                break;
        }
    });

    selectStudiengang.dispatchEvent(new Event('change'));

    saveButton.onclick = deselectUnshownDivStudienrichtung(idStudienrichtung);
}
