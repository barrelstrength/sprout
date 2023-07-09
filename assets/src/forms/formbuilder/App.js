import Alpine from 'alpinejs';
import {FormBuilder} from './FormBuilder';
// import {FormTab} from './FormTab';
import {FormField} from './FormField';

window.Alpine = Alpine;

Alpine.data('FormBuilder', FormBuilder);
// Alpine.data('FormTab', FormTab);
Alpine.data('FormField', FormField);

Alpine.start();
