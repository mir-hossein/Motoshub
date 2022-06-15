/**
 * Created by ismail on 2/26/18.
 */
var QUESTIONS_Question = function (uniqueId, questionId, reloadUrl) {
    this.optionList = [];
    this.reloadUrl = reloadUrl;
    this.questionId = questionId;
    this.uniqueId = uniqueId;
    if (typeof questionId === 'string')
        this.newQuestion = true;
    else
        this.newQuestion = false;
    this.options = '';
};

QUESTIONS_Question.prototype = {
    addOption: function (option) {
        this.optionList.push(option);
    },
    setAddOption: function (addOption) {
        this.addOptionCmp = addOption;
    },
    reloadUI: function () {
        var self = this;
        var data = this.newQuestion ? {questionId: this.questionId, newQuestion: this.newQuestion, options: this.options} :{questionId: this.questionId};
        sendAjax(
            this.reloadUrl,
            data,
            'json',
            function (result) {
                $('#' + self.uniqueId).replaceWith(result.result);
                try {
                    if (result.js)
                        for (var i = 0; i < result.js.length; i++)
                            eval(result.js[i]);
                    if (result.js_files && result.js_files.length > 0)
                        getOW().addScriptFiles(result.js_files);
                } catch (e) {
                }
            }
        );
    },
    edit: function (data) {
        if (data.newQuestion === 'true'){
            var options = $('#question_data').val();
            options = JSON.parse(options);
            options.push(data.option);
            options = JSON.stringify(options);
            $('#question_data').val(options);
            options = JSON.parse(options);
            options.shift();
            options.shift();
            question_map[data.questionId].options = JSON.stringify(options);
            question_map[data.questionId].reloadUI();
        }
        if (editFloatBox) {
            editFloatBox.close();
            editFloatBox = null;
        }
        this.reloadUI();
    }
};

QUESTIONS_Option = function (uniqueId, optionId, ajaxUrls, questionId, answered, multiple, answerError) {
    this.ajaxUrls = ajaxUrls;
    this.optionId = optionId;
    this.uniqueId = uniqueId;
    this.questionId = questionId;
    this.multiple = multiple;
    this.answered = answered;
    this.answerError = answerError;
    this.checkbox = $('#' + this.uniqueId + ' input[type=checkbox]');
    this.radio = $('#' + this.uniqueId + ' input[type=radio]');
    if (this.answered) {
        this.checkbox.prop('checked', true);
        this.radio.prop('checked', true);
    }
    this.content = $('#' + this.uniqueId + ' div.qa-content-wrap');
    this.infoButton1 = $('#' + this.uniqueId + ' div.questions-ic-info:eq( 0 )');
    this.infoButton2 = $('#' + this.uniqueId + ' div.questions-ic-info:eq( 1 )');
    this.text = $('#' + this.uniqueId + ' div.qa-text');
    this.deleteButton = $('#' + this.uniqueId + ' div.qa-delete-option');
    this.editButton = $('#' + this.uniqueId + ' div.qa-edit-option');
    var self = this;
    this.checkbox.change(function () {
        self.answer(self);
    });
    this.radio.change(function () {
        self.answer(self);
    });
    this.infoButton1.click(function () {
        var info = $('#' + self.uniqueId + ' div.q-info');
        if (info.css('display') === "none") {
            info.css('display', "inline-block");
            self.infoButton1.css('display', 'none');
            self.text.css('text-overflow', 'unset');
            self.text.css('white-space', 'unset');
            self.text.css('margin-top', '34px');
            self.text.css('margin-left', '0px');
        }
    });
    this.infoButton2.click(function () {
        var info = $('#' + self.uniqueId + ' div.q-info');
        if (info.css('display') === "inline-block") {
            info.css('display', "none");
            self.infoButton1.css('display', 'block');
            self.text.css('text-overflow', 'ellipsis');
            self.text.css('white-space', 'nowrap');
            self.text.css('margin-top', '0px');
            self.text.css('margin-left', '34px');
        }
    });
    this.content.click(function () {
        self.answer(self);
    });
    this.deleteButton.click(function () {
        if(confirm(OW.getLanguageText('base', 'are_you_sure')))
            self.delete(self);
    });
    this.editButton.click(function () {
        self.edit(self);
    });
};

QUESTIONS_Option.prototype = {
    answer: function (self) {
        if(this.answerError === ''){
            sendAjax(
                this.ajaxUrls['answer'],
                {
                    optionId: this.optionId
                },
                'json',
                function (result) {
                    question_map[self.questionId].reloadUI();
                }
            );
        }
        else{
            getOW().error(this.answerError);
        }

    },
    delete: function (self) {
        if(self.optionId==0)
        {
            var inEditingOptions= document.getElementsByName('input_'+self.questionId);
            for (var i = 0; i < inEditingOptions.length; i++) {
                var divElement = document.createElement("div");
                divElement.setAttribute("class", "qa-text");
                divElement.setAttribute("title", ""+inEditingOptions[i].value+"");
                divElement.innerHTML=""+inEditingOptions[i].value+"";
                inEditingOptions[i].parentNode.replaceChild(divElement, inEditingOptions[i]);
            }
            document.getElementById(self.uniqueId).remove();
            var OptionData= [document.getElementById('input_2xigasuw').value,document.getElementById('input_2xigasuv').value];
            var questionOptions= document.getElementById('question_'+self.questionId).getElementsByClassName('qa-text');
            for (var i = 0; i < questionOptions.length; i++) {
                OptionData.push(questionOptions[i].innerHTML); //second console output
            }
            document.getElementById("question_data").value= JSON.stringify(OptionData);
        }
        else {
            sendAjax(
                this.ajaxUrls['delete'],
                {
                    optionId: this.optionId
                },
                'json',
                function (result) {
                    question_map[self.questionId].reloadUI();
                }
            );
        }
    },
    edit: function (self) {
        if(self.optionId==0)
        {
            var inEditingOptions= document.getElementsByName('input_'+self.questionId);
            for (var i = 0; i < inEditingOptions.length; i++) {
                var divElement = document.createElement("div");
                divElement.setAttribute("class", "qa-text");
                divElement.setAttribute("title", ""+inEditingOptions[i].value+"");
                divElement.innerHTML=""+inEditingOptions[i].value+"";
                inEditingOptions[i].parentNode.replaceChild(divElement, inEditingOptions[i]);
            }
            var inputEditElement = document.createElement("INPUT");
            inputEditElement.setAttribute("type", "text");
            inputEditElement.setAttribute("id", 'input_'+self.uniqueId);
            inputEditElement.setAttribute("class", self.uniqueId);
            inputEditElement.setAttribute("name", 'input_'+self.questionId);
            inputEditElement.setAttribute("data-questionId",self.questionId);
            inputEditElement.setAttribute("value",self.content[0].innerText);
            // replace el with newEL
            if( self.content[0].getElementsByClassName("qa-text")[0]!=undefined) {
                self.content[0].getElementsByClassName("qa-text")[0].parentNode.replaceChild(inputEditElement, self.content[0].getElementsByClassName("qa-text")[0]);
                $('#input_'+self.uniqueId).on('keydown', function(e) {
                    if (e.which == 13) {
                        var divElement = document.createElement("div");
                        divElement.setAttribute("class", "qa-text");
                        divElement.setAttribute("title", ""+e.target.value+"");
                        divElement.innerHTML=""+e.target.value+"";
                        e.target.parentNode.replaceChild(divElement, e.target);
                        var OptionData= [document.getElementById('input_2xigasuw').value,document.getElementById('input_2xigasuv').value];
                        var questionOptions= document.getElementById('question_'+e.target.getAttribute('data-questionid')).getElementsByClassName('qa-text');
                        for (var i = 0; i < questionOptions.length; i++) {
                            OptionData.push(questionOptions[i].innerHTML); //second console output
                        }
                        document.getElementById("question_data").value= JSON.stringify(OptionData);
                    }
                });
            }
        }
        else {
            if (mobile)
                editFloatBox = OWM.ajaxFloatBox('IISQUESTIONS_MCMP_EditOption', [self.optionId], {
                    width: 700,
                    iconClass: 'ow_ic_add'
                });
            else
                editFloatBox = OW.ajaxFloatBox('IISQUESTIONS_CMP_EditOption', [self.optionId], {
                    width: 700,
                    iconClass: 'ow_ic_add'
                });
        }
    }
};

QUESTIONS_OptionList = function () {

};

var QUESTIONS_AddOption = function (uniqueId, questionId, addOptionAjaxUrl) {
    this.questionId = questionId;
    this.addOptionAjaxUrl = addOptionAjaxUrl;
    this.button = $('#' + uniqueId);
    if (question_map[questionId] !== undefined)
        question_map[questionId].setAddOption(this);
    var self = this;
    this.button.click(function () {
        self.pop_up();
    });
};

QUESTIONS_AddOption.prototype = {
    pop_up: function () {
        editFloatBox = OW.ajaxFloatBox('IISQUESTIONS_MCMP_AddOptionFloatBox', [this.questionId, question_map[this.questionId].newQuestion], {
            width: '700px',
            title: OW.getLanguageText('iisquestions', 'question_add_option_inv')
        });
    }
};

var CreateQuestionForm = function (formName, ajaxUrl, infoUrl) {
    this.formName = formName;
    this.infoUrl = infoUrl;
    this.ajaxUrl = ajaxUrl;
    this.form = $('form[name = ' + formName + ']');
    window.owForms[formName].bind('success', this.submitForm);
    this.options = $("form[name = " + formName + "] input[name = 'options']");
    this.options.addClass('last');
    this.options.focusin(this.addOption);
    createQuestionForm = this;
    this.optionNum = 1;
};

CreateQuestionForm.prototype = {
    submitForm: function (result) {
        if (result.questionId)
            questionCreated(result.questionId,result.html,result.js,result.js_files);
    },
    addQuestion: function (question_id) {
        sendAjax(this.infoUrl, {
            questionId: question_id
        }, 'json', function (result) {
            var list = $('div.questions-list');
            list.prepend('<div><div id="' + result.uniqueId + '"></div></div>');
            question_map[question_id] = new QUESTIONS_Question(result.uniqueId, question_id, result.reloadUrl);
            question_map[question_id].reloadUI();
        });
    },
    addOption: function () {
        var self = createQuestionForm;
        var input = $(this);
        if (input.hasClass('last')) {
            var newInput = input.clone();
            newInput.focusin(createQuestionForm.addOption);
            input.removeClass('last');
            input.addClass('destroy');
            input.after(newInput);
            var name = input.attr('name');
            input.attr('name', name + '_' + self.optionNum);
            var id = 'input_' + self.makeid();
            input.attr('id', id);
            window.owForms[self.formName].addElement(new OwFormElement(id,name + '_' + self.optionNum));
            self.optionNum = self.optionNum + 1;
            input.val('');
        }
    },
    makeid: function () {
        var text = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for (var i = 0; i < 5; i++)
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        return text;
    }
};

var QUESTIONS_QuestionDetails = function (questionId, subscribeUrl, editUrl, subscribeError) {
    this.questionId = questionId;
    this.subscribeUrl = subscribeUrl;
    this.editUrl = editUrl;
    this.subscribeError = subscribeError;
    var self = this;
    this.subscribeLink = $('#iisquestions_subscribe_' + this.questionId);
    this.subscribeLink.click(function () {
        self.subscribe(self);
    });
    this.editLink = $('#iisquestions_edit_' + this.questionId);
    this.editLink.click(function () {
        self.editQuestion(self);
    });
};

QUESTIONS_QuestionDetails.prototype = {
    subscribe: function (self) {
        if(this.subscribeError === ''){
            sendAjax(self.subscribeUrl, {questionId: self.questionId}, 'json', function (result) {
                getOW().info(result.msg);
                var aTag = $('#iisquestions_subscribe_' + self.questionId);
                if (mobile) {
                    var span = aTag.find('span');
                    span.text(result.title);
                } else
                    aTag.text(result.title);
            });
        }
        else{
            getOW().error(this.subscribeError);
        }

    },
    editQuestion: function (self) {
        editFloatBox = getOW().ajaxFloatBox('IISQUESTIONS_MCMP_EditQuestion', [self.questionId], {
            width: 700,
            iconClass: 'ow_ic_add'
        })
    }
};

setUpCreateQuestion = function (editUrl,createUrl, context, contextId, js, createParams) {
    setUpQuestion(createParams);
    var addButton = $("#IISQUESTIONS_Add");
    addButton.click(function () {
        if (addButton.hasClass('iisquestions_add')) {
            addButton.removeClass('iisquestions_add');
            addButton.addClass('iisquestions_remove');
            $('#new_question').show();
            $('#question_hidden').val(false);
        } else {
            addButton.removeClass('iisquestions_remove');
            addButton.addClass('iisquestions_add');
            $('#question_hidden').val(true);
            $('#new_question').hide();
        }
    });
    $('form[name = "newsfeed_update_status"]').submit(
        function (e) {
            try {
                window.owForms['newsfeed_update_status'].validate();
                if (addButton.hasClass('iisquestions_remove')) {
                    addButton.removeClass('iisquestions_remove');
                    addButton.addClass('iisquestions_add');
                    $('#question_id').val('');
                    $('#question_data').val(JSON.stringify(['only_for_me','one_answer']));
                    $('#new_question').remove();
                    questionCreated(editUrl,createUrl, context, contextId);
                }
            } catch (exception) {
            }
        }
    );
};

questionCreated = function (editUrl,createUrl, context, contextId) {
    sendAjax(createUrl, {allowAddOptions:'only_for_me',question_context:context,question_context_id:contextId}, 'json', function (result) {
        setUpQuestion(result);
    });
};
setUpQuestion = function( createParams ){
    $('#question_id').val(createParams.questionId);
    var iDiv = document.createElement('div');
    iDiv.className = 'clearfix';
    iDiv.id = 'new_question';
    $('form[name = "newsfeed_update_status"]').append(iDiv);
    $('#new_question').html(createParams.html);
    try {
        if (createParams.js)
            for (var i = 0; i < createParams.js.length; i++)
                eval(createParams.js[i]);
    } catch (e) {
    }finally {
        question_map[createParams.questionId].newQuestion = true;
    }
    $('#new_question').append(createParams.questionSetting);
    $('#new_question select[name="allowAddOptions"]').change(function () {
        var value = "";
        $( "option:selected" ,$(this)).each(function() {
            value = $( this ).attr('value');
        });
        var data = $('#question_data').val();
        data = JSON.parse(data);
        data[0] = value;
        data = JSON.stringify(data);
        $('#question_data').val(data);
    });
    $('#new_question select[name="allowMultipleAnswers"]').change(function () {
        var value = "";
        $( "option:selected" ,$(this)).each(function() {
            value = $( this ).attr('value');
        });
        var data = $('#question_data').val();
        data = JSON.parse(data);
        data[1] = value;
        data = JSON.stringify(data);
        $('#question_data').val(data);
    });
    $('#new_question').hide();
    $('#question_hidden').val(true);
};

sendAjax = function (url, data, type, onSuccess) {
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        dataType: type,
        success: function (result) {
            if (result) {
                if (!result.error) {
                    onSuccess(result);
                }
                else {
                    getOW().error(result.error);
                }
            }
        }
    });
};

getOW = function () {
    if (mobile)
        return OWM;
    else
        return OW;
};

serializeObject = function (form) {
    var o = {};
    var a = form.serializeArray();
    $.each(a, function () {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

/*
This code is not needed anymore
TODO it must be removed after comprehensive testing
 */
/*OW.bind('base.newsfeed_content.edited',function (data) {
    if(data.entityId && data.entityType){
        sendAjax(window.question_info_url, {
            entityId: data.entityId,
            entityType: data.entityType
        }, 'json', function (result) {
            if(!result.error){
                var content = $('#action-feed1-'+data.itemId+' div.ow_newsfeed_content');
                content.html(content.html()+ '<div><div id="' + result.uniqueId + '"></div></div>');
                question_map[result.questionId].reloadUI();
            }
        });
    }
});*/

createQuestionForm = null;
editFloatBox = null;
question_map = [];
question_details_map = [];
mobile = false;
