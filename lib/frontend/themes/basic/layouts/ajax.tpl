{$this->beginPage()}
{use class="frontend\design\Info"}
{$content}
{\common\components\google\widgets\GoogleTagmanger::trigger()}
<script type="text/javascript">
  if (typeof cssArray === 'undefined') {
    var cssArray = [];
  }
  tl(function(){
    {foreach Info::getCssArray(THEME_NAME, '.p-'|cat:$this->context->id|cat:'-'|cat:$this->context->action->id) as $key => $item}
    if (!cssArray['{addslashes($key)}'] || '{addslashes($key)}' === 'blocks') {
      cssArray['{addslashes($key)}'] = 1;
      $('head style:last').append('{str_replace("\n", '', addslashes($item))}')
    }
    {/foreach}
  })
</script>
{$this->endBody()}
{$this->endPage(true)}