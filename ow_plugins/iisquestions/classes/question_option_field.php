<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 3/4/18
 * Time: 9:01 AM
 */
class IISQUESTIONS_CLASS_QuestionOptionField extends InvitationFormElement
{
    /**
     * @see FormElement::renderInput()
     * @param array $params
     * @return string
     */
    public function renderInput($params = array())
    {
        $value = $this->getValue();
        $count = empty($value) ? 1 : count($value) + 1;
//        $content = $this->renderItem(-1, true);
        for ($i = 0; $i < $count; $i++) {
            if (isset($content))
                $content .= $this->renderItem($i);
            else
                $content = $this->renderItem($i);
        }

        return UTIL_HtmlTag::generateTag('div', array_merge($this->attributes, $params), true, $content);
    }

    private function renderItem($index, $proto = false)
    {
        $value = $this->getValue();

        $inputAttrs = array(
            'type' => 'text',
            'maxlength' => 150,
            'name' => $this->getName() . '[]',
            'class' => 'mt-item-input',
            'value' => empty($value[$index]) ? '' : $value[$index]
        );

        $contAttrs = array(
            'class' => 'mt-item ow_smallmargin'
        );

        if ($proto) {
            $inputAttrs['value'] = '';
            $contAttrs['style'] = 'display: none;';
        }

        if ($this->getHasInvitation() && empty($inputAttrs['value'])) {
            $inputAttrs['value'] = $this->invitation;
            $inputAttrs['class'] .= ' invitation';
        }

        $input = UTIL_HtmlTag::generateTag('input', $inputAttrs);

        return UTIL_HtmlTag::generateTag('div', $contAttrs, true, $input);
    }

    public function getElementJs()
    {
//        $js = UTIL_JsGenerator::newInstance()->newObject('formElement', 'QUESTIONS_CLASS_QuestionOptionField', array(
//            $this->getId(), $this->getName(), ($this->getHasInvitation() ? $this->getInvitation() : false)
//        ));
//
//        /** @var $value Validator */
//        foreach ($this->validators as $value) {
//            $js .= "formElement.addValidator(" . $value->getJsValidator() . ");";
//        }
//
//        return $js;
    }
}