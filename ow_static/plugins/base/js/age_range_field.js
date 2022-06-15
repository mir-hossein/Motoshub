

var AgeRangeField = function( $name, $minAge, $maxAge  )
{
    var self = this;

    var $cont = $('.'+$name);

    this.toAge = $cont.find("select[name='" + $name + "[to]']");
    this.fromAge = $cont.find("select[name='" + $name + "[from]']");

    this.minAge = $minAge;
    this.maxAge = $maxAge;

    this.toAge.change( function()
    {
        self.updateValue();
        if( parseInt(self.fromAge.val()) > parseInt(self.toAge.val()) )
        {

            self.fromAge.val(self.toAge.val());
        }
    } );

    this.fromAge.change( function()
    {
        self.updateValue();
        if( parseInt(self.fromAge.val()) > parseInt(self.toAge.val()) )
        {
            self.toAge.val(self.fromAge.val());
        }
    } );


    this.updateValueJalali = function()
    {
        var tempDate;
        var tmpToAge;
        var tmpFromAge;
        if(typeof(getCookie) == "function" && getCookie("iisjalali")==1)
        {
            tempDate = jalali_to_gregorian(parseInt(self.fromAge.val()), parseInt('1'), parseInt('1'));
            tmpFromAge =tempDate[0];

            tempDate = jalali_to_gregorian(parseInt(self.toAge.val()), parseInt(1), parseInt(1));
            tmpToAge = tempDate[0];

        }

        if( parseInt(tmpFromAge) < parseInt(self.minAge) )
        {
            tmpFromAge = self.minAge;
        }

        if( parseInt(tmpToAge) < parseInt(self.minAge) )
        {
            tmpToAge = self.minAge;
        }

        if( parseInt(tmpFromAge) > parseInt(self.maxAge) )
        {
            tmpFromAge = self.maxAge;
        }

        if( parseInt(self.toAge.val()) > parseInt(self.maxAge) )
        {
            tmpToAge = self.maxAge;
        }

        var tempDate = gregorian_to_jalali(parseInt(tmpFromAge), parseInt(7), parseInt(1));
        self.fromAge.val(tempDate[0]);

        tempDate = gregorian_to_jalali(parseInt(tmpToAge), parseInt(7), parseInt(1));
        self.toAge.val(tempDate[0]);
    }

    this.updateValueGregorian = function()
    {
        if( parseInt(self.fromAge.val()) < parseInt(self.minAge) )
        {
            self.fromAge.val(self.minAge);
        }

        if( parseInt(self.toAge.val()) < parseInt(self.minAge) )
        {
            self.toAge.val(self.minAge);
        }

        if( parseInt(self.fromAge.val()) > parseInt(self.maxAge) )
        {
            self.fromAge.val(self.maxAge);
        }

        if( parseInt(self.toAge.val()) > parseInt(self.maxAge) )
        {
            self.toAge.val(self.maxAge);
        }
    }
    this.updateValue = function()
    {
        if(typeof(getCookie) == "function" && getCookie("iisjalali")==1)
        {
            self.updateValueJalali();
        }
        else
        {
            self.updateValueGregorian();
        }

    }
}

var AgeRangeFormElement = function( id, name ) {
    
    var self = this;
    this.id = id;
    this.name = name;
    //this.input = document.getElementById(id);
    this.to = $('#'+id).find("select[name='" + name + "\[to\]']");//.val(value['to']);
    this.from = $('#'+id).find("select[name='" + name + "\[from\]']");//.val(value['from']);
    this.validators = [];
}

AgeRangeFormElement.prototype = {

    validate: function(){

        var error = false;

        try{
            for( var i = 0; i < this.validators.length; i++ ){
                this.validators[i].validate(this.getValue());
            }
        }catch (e) {
            error = true;
            this.showError(e);
        }

        if( error ){
            throw e;
        }
    },

    addValidator: function( validator ){
        this.validators.push(validator);
    },

    getValue: function(){
        var self = this;
        
        var values = {};
        values['to'] = self.to.val();
        values['from'] = self.from.val();
        return values;
    },

    setValue: function( value ){
        var self = this;

        self.to.val(value['to']);
        self.from.val(value['from']);
    },

    resetValue: function(){
        var self = this;

        self.to.val('');
        self.from.val('');
    },

    showError: function( errorMessage ){
        $('#'+this.id+'_error').append(errorMessage).fadeIn(50);
    },

    removeErrors: function(){
        $('#'+this.id+'_error').empty().fadeOut(50);
    }
}
