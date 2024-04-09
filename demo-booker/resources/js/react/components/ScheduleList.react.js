const React = require('react');
const update = require('react-addons-update');
const Schedule = require('./Schedule.react');
const ScheduleTemplate = require('../constants/ScheduleTemplate');

class ScheduleList extends React.Component {
    constructor(props) {
        super(props);

        this.onAddSchedule = this.onAddSchedule.bind(this);
        this.onDeleteSchedule = this.onDeleteSchedule.bind(this);
        this.onUndoDelete = this.onUndoDelete.bind(this);

        this.state = {
            'schedules': this.props.schedules,
            'intendDelete': [],
        };
    }

    onAddSchedule() {
        const templateCopy = Object.assign({}, ScheduleTemplate);

        templateCopy.id = this.uuid();

        const newState = update(this.state, {
            schedules: {$push: [templateCopy]},
        });

        this.setState(newState);
    }

    onUndoDelete(scheduleListIndex) {
        let newState = null;

        if (this.state.intendDelete.indexOf(scheduleListIndex) !== -1
            || this.state.schedules[scheduleListIndex].operation === 'delete'
        ) {
            newState = update(this.state, {
                intendDelete: { $splice: [[this.state.intendDelete.indexOf(scheduleListIndex), 1]] },
                schedules: { [scheduleListIndex]: { operation: { $set: 'update' }}},
            });
        }

        this.setState(newState);
    }

    onDeleteSchedule(scheduleListIndex) {
        let newState = null;

        // If user tries to delete an existing saved schedule,
        // mark it for deletion instead of removing from the DOM
        if (this.state.schedules[scheduleListIndex].operation === 'update') {
            newState = update(this.state, {
                intendDelete: {$push: [scheduleListIndex]},
                schedules: { [scheduleListIndex]: {operation: {$set: 'delete'}}},
            });
        } else {
            newState = update(this.state, {
                schedules: {$splice: [[scheduleListIndex, 1]]},
            });
        }

        this.setState(newState);
    }

    uuid() {
        let i, random;
        let uuid = '';

        for (i = 0; i < 32; i++) {
            random = Math.random() * 16 | 0;
            if (i === 8 || i === 12 || i === 16 || i === 20) {
                uuid += '-';
            }

            uuid += (i === 12 ? 4 : (i === 16 ? (random & 3 | 8) : random)).toString(16);
        }
        return uuid;
    }

    renderSchedules() {
        return this.state.schedules.map((schedule, i) => {
            return (
                <Schedule
                    key={schedule.id}
                    scheduleListIndex={i}
                    scheduleData={schedule}
                    onDelete={this.onDeleteSchedule}
                    onUndoDelete={this.onUndoDelete}
                    intendDelete={(this.state.intendDelete.indexOf(i) !== -1) || (schedule.operation === 'delete')}
                />
            );
        });
    }

    render() {
        return (
            <div>
                {this.renderSchedules()}
                <button
                    type="button"
                    className="button scheduler__add-schedule"
                    onClick={this.onAddSchedule}
                >
                    + Add a Schedule
                </button>
            </div>
        );
    }
}

module.exports = ScheduleList;