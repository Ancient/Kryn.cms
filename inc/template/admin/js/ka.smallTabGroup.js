ka.smallTabGroup = new Class({
   Extends: ka.tabGroup,
   'className': 'ka-tabGroup-small',

    addButton: function (pTitle, pOnClick, pImageSrc){

        var button = new Element('a', {
            'class': 'ka-tabGroup-item gradient',
            title: pTitle,
            text: pTitle
        }).inject(this.box);

        if (pImageSrc) {
            new Element('img', {
                src: pImageSrc
            }).inject(button, 'top');
        }

        this.setMethods(button, pOnClick);

        return button;
    }

});