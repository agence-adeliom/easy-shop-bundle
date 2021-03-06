import Sortable from 'sortablejs';


const eaProductAttributesHandler = function (event) {
    document.querySelectorAll('button.field-product_attr-add-button:not(.processed)').forEach((addButton) => {
        const collection = addButton.closest('[data-ea-collection-field]');
        if (!collection || addButton.classList.contains('processed')) {
            return;
        }
        EaProductAttributesCollectionProperty.handleAddButton(addButton, collection);
        EaProductAttributesCollectionProperty.updateCollectionItemCssClasses(collection);
        EaProductAttributesCollectionProperty.updateCollectionSortable(collection);
    });

    document.querySelectorAll('button.field-product_attr-remove-button').forEach((deleteButton) => {
        deleteButton.addEventListener('click', () => {
            const collection = deleteButton.closest('[data-ea-collection-field]');
            let item = deleteButton.closest('.field-collection-item');
            let type = item.dataset.attributeType;
            collection.querySelectorAll('[data-block-type="'+type+'"]').forEach((elm) => {
                elm.disabled = false;
            })
            let block = deleteButton.closest('.form-group');
            block.remove();
            document.dispatchEvent(new Event('ea.product_attribute.item-removed'));

            EaProductAttributesCollectionProperty.updateCollectionItemCssClasses(collection);
            EaProductAttributesCollectionProperty.updateCollectionSortable(collection);
        });
    });


    if(event.type === "DOMContentLoaded"){
        document.dispatchEvent(new Event('ea.product_attribute.item-loaded'));
    }


}

window.addEventListener('DOMContentLoaded', eaProductAttributesHandler);
document.addEventListener('ea.product_attribute.item-added', eaProductAttributesHandler);


const EaProductAttributesCollectionProperty = {
    handleAddButton: (addButton, collection) => {
        addButton.addEventListener('click', function() {
            const isArrayCollection = collection.classList.contains('field-array');
            // Use a counter to avoid having the same index more than once
            let numItems = parseInt(collection.dataset.numItems);

            // Remove the 'Empty Collection' badge, if present
            const emptyCollectionBadge = collection.querySelector('.collection-empty');
            if (null !== emptyCollectionBadge) {
                emptyCollectionBadge.outerHTML = isArrayCollection ? '<div class="ea-form-collection-items"></div>' : '<div class="ea-form-collection-items"><div class="accordion border-0 shadow-none"><div class="form-widget-compound"><div></div></div></div></div>';
            }

            const formTypeNamePlaceholder = addButton.dataset.formTypeNamePlaceholder;
            const labelRegexp = new RegExp(formTypeNamePlaceholder + 'label__', 'g');
            const nameRegexp = new RegExp(formTypeNamePlaceholder, 'g');

            addButton.disabled = true

            let newItemHtml = addButton.dataset.prototype
                .replace(labelRegexp, numItems)
                .replace(nameRegexp, numItems);

            collection.dataset.numItems = ++numItems;
            const newItemInsertionSelector = isArrayCollection ? '.ea-form-collection-items' : '.ea-form-collection-items .accordion > .form-widget-compound > div';
            const collectionItemsWrapper = collection.querySelector(newItemInsertionSelector);

            EaProductAttributesCollectionProperty.setInnerHTML(collectionItemsWrapper, newItemHtml).then(() => {
                // for complex collections of items, show the newly added item as not collapsed
                if (!isArrayCollection) {
                    EaProductAttributesCollectionProperty.updateCollectionItemCssClasses(collection);
                    EaProductAttributesCollectionProperty.updateCollectionSortable(collection);

                    const collectionItems = collectionItemsWrapper.querySelectorAll('.field-collection-item');
                    const lastElement = collectionItems[collectionItems.length - 1];
                    const lastElementCollapseButton = lastElement.querySelector('.accordion-button');
                    lastElementCollapseButton.classList.remove('collapsed');
                    const lastElementBody = lastElement.querySelector('.accordion-collapse');
                    lastElementBody.classList.add('show');
                }

                document.dispatchEvent(new Event('ea.product_attribute.item-added'));
                document.dispatchEvent(new Event('ea.collection.item-added'));
            })

        });
        addButton.classList.add('processed');
    },

    updateCollectionSortable: (collection) => {
        if (null === collection) {
            return;
        }

        if(collection.querySelector(".ea-form-collection-items .accordion > .form-widget-compound > div")){
            if(collection.sortable){
                collection.sortable.destroy();
                collection.sortable = null;
            }

            collection.sortable = Sortable.create(collection.querySelector(".ea-form-collection-items .accordion > .form-widget-compound > div"),{
                handle: '.field-product_attr-drag-button',
                direction: 'vertical',
                onEnd: function (evt) {
                    EaProductAttributesCollectionProperty.updateCollectionItemCssClasses(collection);
                },
            });
        }
    },
    updateCollectionItemCssClasses: (collection) => {
        if (null === collection) {
            return;
        }

        const collectionItems = collection.querySelectorAll('.field-collection-item');
        collectionItems.forEach((item, key) => {
            item.querySelectorAll('[name]').forEach((input) => {
                if(input.name.includes("[position]")){
                    input.value = key
                }
            })
        })

        collectionItems.forEach((item) => item.classList.remove('field-collection-item-first', 'field-collection-item-last'));
        const firstElement = collectionItems[0];
        if (undefined === firstElement) {
            return;
        }
        firstElement.classList.add('field-collection-item-first');

        const lastElement = collectionItems[collectionItems.length - 1];
        if (undefined === lastElement) {
            return;
        }
        lastElement.classList.add('field-collection-item-last');

    },
    loadStyle(src) {
        return new Promise(function (resolve, reject) {
            let link = document.createElement('link');
            link.href = src;
            link.rel = 'stylesheet';

            link.onload = () => resolve(link);
            link.onerror = () => reject(new Error(`Style load error for ${src}`));

            document.head.append(link);
        });
    },
    loadScript(src) {
        return new Promise(function (resolve, reject) {
            let script = document.createElement('script');
            script.src = src;
            script.type = "text/javascript"

            script.onload = () => resolve(script);
            script.onerror = () => reject(new Error(`Style load error for ${src}`));

            document.head.append(script);
        });
    },
    setInnerHTML(elm, html) {
        var tmp = document.createElement("div");
        tmp.innerHTML = html;

        let remote = [];
        Array.from(tmp.querySelectorAll("script")).forEach( oldScript => {
            if(oldScript.src){
                remote.push(EaProductAttributesCollectionProperty.loadScript(oldScript.src));
            }
        });

        return Promise.all(remote).then(values => {
            elm.insertAdjacentHTML('beforeend', html);
            Array.from(elm.lastElementChild.querySelectorAll("script")).forEach( oldScript => {
                if(!oldScript.src){
                    const newScript = document.createElement("script");
                    Array.from(oldScript.attributes).forEach( attr => newScript.setAttribute(attr.name, attr.value) );
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                }
            });
        });
    }
};
