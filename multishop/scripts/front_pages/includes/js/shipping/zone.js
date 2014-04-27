var PackageId = "22631";
function SelectSentPrice(ShowId, Option) {
    document.getElementById(ShowId + '_Show_NONE').style.display = 'none';
    document.getElementById(ShowId + '_Show_FIXED').style.display = 'none';
    document.getElementById(ShowId + '_Show_NOPRICEHIGHER').style.display = 'none';
    document.getElementById(ShowId + '_Show_HIGHERPRICE').style.display = 'none';
    document.getElementById(ShowId + '_Show_WEIGHT').style.display = 'none';

    document.getElementById(ShowId + '_Show_' + Option).style.display = '';
}

function UpdateWeightPrice(Level, PackId, Zone) {
    Level = parseInt(Level);
    var Level_sub = Level - 1;
    var EndWeightNext = "";
    //alert(Level_sub);
    //console.log(PackId+'_'+Zone+'_EndWeightLevel'+Level);
    BeginWeight = document.getElementById(PackId + '_' + Zone + '_BeginWeightLevel' + Level);
    EndWeight = document.getElementById(PackId + '_' + Zone + '_EndWeightLevel' + Level_sub);
    EndWeightNext = document.getElementById(PackId + '_' + Zone + '_EndWeightLevel' + Level);
    //console.log(EndWeightNext);
    //alert(EndWeightNext);
    EndWeightNext_blank = PackId + '_' + Zone + '_EndWeightLevel' + Level;
    Price = document.getElementById(PackId + '_' + Zone + '_PriceLevel' + Level);
    Tot = document.getElementById(PackId + '_' + Zone + '_TotLevel' + Level);
    Niv = document.getElementById(PackId + '_' + Zone + '_NivLevel' + Level);
    //alert("Zone: "+Zone+"\nLevel: "+Level+"\n"+EndWeight);

    if (EndWeight.options.length == 0) {
        EndWeightValue = '';
    } else {
        EndWeightValue = EndWeight.options[EndWeight.selectedIndex].value;
        //alert(PackId+'_'+Zone+'_EndWeightLevel'+Level_sub);
        //alert(EndWeight.selectedIndex);
        //alert(EndWeightValue);
    }


    if (EndWeightValue != 101 && EndWeightValue) {
        EndWeightNext.style.display = '';
        BeginWeight.style.display = '';
        Price.style.display = '';
        Tot.style.display = '';
        Niv.style.display = '';

        BeginWeight.innerHTML = EndWeight.options[EndWeight.selectedIndex].text + " KG";
    } else {
        if (Level != 1) {
            EndWeightNext.style.display = 'none';
            BeginWeight.style.display = 'none';
            Price.style.display = 'none';
            Tot.style.display = 'none';
            Niv.style.display = 'none';

        }
        BeginWeight.innerHTML = '';
    }
    CreateWeightList(EndWeightNext_blank, EndWeightValue, Level, PackId, Zone);
}

function CreateWeightList(Input, BeginValue, Level, PackId, Zone) {

    Obj = document.getElementById(Input);

    var newOpt;
    var curSelectedIndex;

    BeginValue = parseFloat(BeginValue);
    curSelected = Obj.value;

    while (Obj.options.length) {
        Obj.remove(0);
    }


    for (var i = 0; i < 102; i++) {
        if (Level != 13) {
            if (i > BeginValue && i != 101) {
                newOpt = document.createElement("OPTION");
                newOpt.text = i;
                newOpt.value = i;
                Obj.options.add(newOpt);
                if (i == curSelected) {
                    curSelectedIndex = Obj.options.length - 1;
                }
            }
            if (i < 1) {
                Value = parseFloat(i + ".02");
                if (BeginValue < Value) {
                    newOpt = document.createElement("OPTION");
                    newOpt.text = i + ",02";
                    newOpt.value = i + ".02";
                    Obj.options.add(newOpt);
                }
                if (Value == curSelected) {
                    curSelectedIndex = Obj.options.length - 1;
                }

                Value = parseFloat(i + ".05");
                if (BeginValue < Value) {
                    newOpt = document.createElement("OPTION");
                    newOpt.text = i + ",05";
                    newOpt.value = i + ".05";
                    Obj.options.add(newOpt);
                }
                if (Value == curSelected) {
                    curSelectedIndex = Obj.options.length - 1;
                }

                Value = parseFloat(i + ".1");
                if (BeginValue < Value) {
                    newOpt = document.createElement("OPTION");
                    newOpt.text = i + ",1";
                    newOpt.value = i + ".1";
                    Obj.options.add(newOpt);
                }
                if (Value == curSelected) {
                    curSelectedIndex = Obj.options.length - 1;
                }

                Value = parseFloat(i + ".25");
                if (BeginValue < Value) {
                    newOpt = document.createElement("OPTION");
                    newOpt.text = i + ",25";
                    newOpt.value = i + ".25";
                    Obj.options.add(newOpt);
                }
                if (Value == curSelected) {
                    curSelectedIndex = Obj.options.length - 1;
                }

                Value = parseFloat(i + ".35");
                if (BeginValue < Value) {
                    newOpt = document.createElement("OPTION");
                    newOpt.text = i + ",35";
                    newOpt.value = i + ".35";
                    Obj.options.add(newOpt);
                }
                if (Value == curSelected) {
                    curSelectedIndex = Obj.options.length - 1;
                }

                Value = parseFloat(i + ".5");
                if (BeginValue < Value) {
                    newOpt = document.createElement("OPTION");
                    newOpt.text = i + ",5";
                    newOpt.value = i + ".5";
                    Obj.options.add(newOpt);
                }
                if (Value == curSelected) {
                    curSelectedIndex = Obj.options.length - 1;
                }

                Value = parseFloat(i + ".75");
                if (BeginValue < Value) {
                    newOpt = document.createElement("OPTION");
                    newOpt.text = i + ",75";
                    newOpt.value = i + ".75";
                    Obj.options.add(newOpt);
                }
                if (Value == curSelected) {
                    curSelectedIndex = Obj.options.length - 1;
                }
            }

            if (i < 5 && i > 0) {
                if (i > BeginValue || (i + 1 > BeginValue && i <= BeginValue)) {
                    Value = parseFloat(i + ".25");
                    if (BeginValue < Value) {
                        newOpt = document.createElement("OPTION");
                        newOpt.text = i + ",25";
                        newOpt.value = i + ".25";
                        Obj.options.add(newOpt);
                    }
                    if (Value == curSelected) {
                        curSelectedIndex = Obj.options.length - 1;
                    }
                    Value = parseFloat(i + ".5");
                    if (BeginValue < Value) {
                        newOpt = document.createElement("OPTION");
                        newOpt.text = i + ",5";
                        newOpt.value = i + ".5";
                        Obj.options.add(newOpt);
                    }
                    if (Value == curSelected) {
                        curSelectedIndex = Obj.options.length - 1;
                    }
                    Value = parseFloat(i + ".75");
                    if (BeginValue < Value) {
                        newOpt = document.createElement("OPTION");
                        newOpt.text = i + ",75";
                        newOpt.value = i + ".75";
                        Obj.options.add(newOpt);
                    }
                    if (Value == curSelected) {
                        curSelectedIndex = Obj.options.length - 1;
                    }
                }
            } else if (i >= 10 && i < 50) {
                i = i + 4;
            } else if (i == 50) {
                i = i + 49;
            }
        }
        if (i > 100) {
            if (BeginValue < i) {
                newOpt = document.createElement("OPTION");
                newOpt.text = " End";
                newOpt.value = 101;
                Obj.options.add(newOpt);
            }
        }
    }

    if (curSelectedIndex) {
        if (Obj.options.length > 0) {
            Obj.selectedIndex = curSelectedIndex;
        }
    } else if (Level < 10) {
        if (!curSelectedIndex) {
            if (Obj.options.length > 0) {
                if (Level != 13) {
                    var Level_sub = Level + 1;
                    Testje = document.getElementById(PackId + '_' + Zone + '_EndWeightLevel' + Level_sub);
                }
                if (Testje.value && Level != 1) {
                    Obj.selectedIndex = 0;
                } else {
                    Obj.selectedIndex = Obj.options.length - 1;
                }

            }
        }
        Level = parseInt(Level);
        //console.log(Zone);
        UpdateWeightPrice(Level + 1, PackId, Zone);
    } else if (Level == 13) {
        if (!curSelectedIndex) {
            if (Obj.options.length > 0) {
                Obj.selectedIndex = Obj.options.length - 1;
            }
        }
        //	alert('End of Create');
    }

}