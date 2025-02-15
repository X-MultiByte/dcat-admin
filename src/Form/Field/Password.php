<?php

namespace Dcat\Admin\Form\Field;

use Illuminate\Support\Str;

class Password extends Text
{
    /**
     * @var bool
     */
    protected bool $toggle = true;
    
    /**
     * @var string
     */
    protected $show_icon = 'feather icon-eye';
    
    /**
     * @var string
     */
    protected $hide_icon = 'feather icon-eye-off';
    
    protected $default = false;
    
    /**
     * @var string
     */
    protected string $id = '';
    
    public function showIcon( $icon = 'feather icon-eye' )
    {
        $this->show_icon = $icon;
        
        return $this;
    }
    
    public function hideIcon( $icon = 'feather icon-eye-off' )
    {
        $this->hide_icon = $icon;
        
        return $this;
    }
    
    public function show()
    {
        $this->default = true;
        
        return $this;
    }
    
    public function hide()
    {
        $this->default = false;
        
        return $this;
    }
    
    /**
     * Return element name.
     *
     * @return string
     */
    public function name()
    {
        return str_replace([ '.', '-', '[', ']' ], '_', $this->getElementName());
    }
    
    /**
     * Set password field id.
     *
     * @param  string  $id
     *
     * @return $this
     */
    public function setId( string $id = '' )
    {
        if (empty($id)) {
            $this->id = sprintf("%s_%s", $this->name(), Str::random(5));
        }
        
        $this->defaultAttribute('id', $this->id);
        
        return $this;
    }
    
    /**
     * Return password field id.
     *
     * @return string
     */
    public function id()
    {
        if (! $this->id) {
            $this->setId();
        }
        
        return $this->id;
    }
    
    /**
     * Render password field.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function render()
    {
        if ($this->toggle) {
            if ($this->default) {
                $this->defaultAttribute('type', 'text');
            }
            else {
                $this->defaultAttribute('type', 'password');
            }
            
            $this->addScript();
            $append = '<a class="btn-password-visibility" data-target="' . $this->id() . '">';
            $append .= '<span class="input-group-text bg-white" id="show-' . $this->id() . '">';
            $append .= '<i class="' . $this->show_icon . '"></i>';
            $append .= '</span>';
            $append .= '<span class="input-group-text bg-white" id="hide-' . $this->id() . '" style="display: none;">';
            $append .= '<i class="' . $this->hide_icon . '"></i>';
            $append .= '</span>';
            $append .= '</a>';
            $this->append($append);
        }
        else {
            $this->defaultAttribute('type', 'password');
        }
        
        return parent::render();
    }
    
    /**
     * @return \Dcat\Admin\Form\Field\Password
     */
    public function addScript()
    {
        $this->script = <<<SCRIPT
            $("a.btn-password-visibility").on("click", function() {
                var target = $(this).data("target");
                var input = $("#"+target);
            
                if(input.attr("type") == "password") {
                    $("#"+target).attr("type", "text");
                    $("#show-"+target).hide();
                    $("#hide-"+target).show();
                }
                else if(input.attr("type") == "text") {
                    $("#"+target).attr("type", "password");
                    $("#show-"+target).show();
                    $("#hide-"+target).hide();
                }
            })
            SCRIPT;
        
        return $this;
    }
    
    public function toggle( $enabled = true )
    {
        $this->toggle = $enabled;
        
        return $this;
    }
}
