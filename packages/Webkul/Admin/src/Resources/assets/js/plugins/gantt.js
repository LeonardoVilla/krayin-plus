import Gantt from 'frappe-gantt';
import '../../css/vendor/frappe-gantt.css';

export default {
    install: (app) => {
        app.config.globalProperties.$Gantt = Gantt;
    },
};
