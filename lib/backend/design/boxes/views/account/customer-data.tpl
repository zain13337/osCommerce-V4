{use class="Yii"}
<form action="{Yii::getAlias('@web')}/design/box-save" method="post" id="box-save">
    <input type="hidden" name="id" value="{$id}"/>
    <div class="popup-heading">
        {$smarty.const.TABLE_TEXT_NAME}
    </div>
    <div class="popup-content">




        <div class="tabbable tabbable-custom">
            <ul class="nav nav-tabs">

                <li class="active"><a href="#type" data-toggle="tab">{$smarty.const.TABLE_TEXT_NAME}</a></li>
                <li><a href="#style" data-toggle="tab">{$smarty.const.HEADING_STYLE}</a></li>
                <li><a href="#align" data-toggle="tab">{$smarty.const.HEADING_WIDGET_ALIGN}</a></li>
                <li><a href="#visibility" data-toggle="tab">{$smarty.const.TEXT_VISIBILITY_ON_PAGES}</a></li>

            </ul>
            <div class="tab-content">
                <div class="tab-pane active menu-list" id="type">




                    <div class="setting-row">
                        <label for="">{$smarty.const.CHOOSE_CUSTOMERS_DATA}</label>
                        <select name="setting[0][customers_data]" id="" class="form-control">
                            <option value=""{if $settings[0].customers_data == ''} selected{/if}></option>
                            <option value="name"{if $settings[0].customers_data == 'name'} selected{/if}>{$smarty.const.TABLE_TEXT_NAME}</option>
                            <option value="dob"{if $settings[0].customers_data == 'dob'} selected{/if}>{$smarty.const.ENTRY_DATE_OF_BIRTH}</option>
                            <option value="email"{if $settings[0].customers_data == 'email'} selected{/if}>{$smarty.const.TEXT_EMAIL}</option>
                            <option value="telephone"{if $settings[0].customers_data == 'telephone'} selected{/if}>{$smarty.const.TEXT_TELEPHONE}</option>
                        </select>
                    </div>
                    <div class="setting-row">
                        <label for="">{$smarty.const.HIDE_PARENTS_IF_EMPTY}</label>
                        <select name="setting[0][hide_parents]" id="" class="form-control">
                            <option value=""{if $settings[0].hide_parents == ''} selected{/if}>{$smarty.const.TEXT_NO}</option>
                            <option value="1"{if $settings[0].hide_parents == '1'} selected{/if}>1</option>
                            <option value="2"{if $settings[0].hide_parents == '2'} selected{/if}>2</option>
                            <option value="3"{if $settings[0].hide_parents == '3'} selected{/if}>3</option>
                            <option value="4"{if $settings[0].hide_parents == '4'} selected{/if}>4</option>
                        </select>
                    </div>






                </div>
                <div class="tab-pane" id="style">
                    {include '../include/style.tpl'}
                </div>
                <div class="tab-pane" id="align">
                    {include '../include/align.tpl'}
                </div>
                <div class="tab-pane" id="visibility">
                    {include '../include/visibility.tpl'}
                </div>

            </div>
        </div>


    </div>
    <div class="popup-buttons">
        <button type="submit" class="btn btn-primary btn-save">{$smarty.const.IMAGE_SAVE}</button>
        <span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span>
    </div>
</form>