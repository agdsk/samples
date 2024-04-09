const React = require('react');
const TimeSelector = require('./TimeSelector.react');

const { PropTypes } = React;

class ScheduleDay extends React.Component {
    constructor(props) {
        super(props);

        this.onTimeChange = this.onTimeChange.bind(this);

        this.state = {
            dayStart: this.props.dayStart || 'none',
            dayEnd: this.props.dayEnd || 'none',
            breakStart: this.props.breakStart || 'none',
            breakEnd: this.props.breakEnd || 'none',
        };
    }

    onTimeChange(fieldKey, value) {
        const newState = {};

        newState[fieldKey] = value;

        // If user clears break start, clear break end
        if (value === 'none' && fieldKey === 'breakStart') {
            newState.breakEnd = 'none';
        }

        // If user clears day start, clear everything
        if (value === 'none' && fieldKey === 'dayStart') {
            newState.breakStart = 'none';
            newState.breakEnd = 'none';
            newState.dayEnd = 'none';
        }

        // If user sets dayStart to later than something else, clear everything
        if (fieldKey === 'dayStart') {
            if (
                value > this.state.dayEnd
                || value > this.state.breakStart
                || value > this.state.breakEnd
            ) {
                newState.breakStart = 'none';
                newState.breakEnd = 'none';
                newState.dayEnd = 'none';
            }
        }

        this.setState(newState);
    }

    getBreakTime() {
        const {breakStart, breakEnd} = this.state;
        return (!isNaN(breakEnd) && !isNaN(breakStart))
            ? (parseInt(breakEnd, 10) - parseInt(breakStart, 10))
            : 0;
    }

    getDemoCount() {
        const {
            stations,
        } = this.props;

        const {
            dayStart,
            dayEnd,
        } = this.state;

        let count = null;
        if (isNaN(dayStart) || isNaN(dayEnd)) {
            count = null;
        } else {
            if (parseInt(dayEnd, 10) <= parseInt(dayStart, 10)) {
                count = '(Invalid range)';
            } else if (isNaN(stations) || stations === 0) {
                count = '(No stations)';
            } else {
                count = `${(((parseInt(dayEnd, 10) - parseInt(dayStart, 10)) - this.getBreakTime()) / 30) * stations} demos`;
            }
        }

        return count;
    }

    render() {
        const {
            dayNumber,
            niceName,
            scheduleID,
        } = this.props;

        const {
            dayStart,
            dayEnd,
            breakStart,
            breakEnd,
        } = this.state;

        const dayEndStartLimit = Math.max(
            isNaN(dayStart) ? -Infinity : dayStart,
            isNaN(breakEnd) ? -Infinity : breakEnd,
        );

        const breakEndStartLimit = Math.max(
            isNaN(dayStart) ? -Infinity : dayStart,
            isNaN(breakStart) ? -Infinity : (parseInt(breakStart, 10) + 30),
        );

        return (
            <div className="scheduler__day">
                <span className="table-narrow__label-cell">{niceName}</span>
                <TimeSelector
                    fieldName={`schedules[${scheduleID}][day_${dayNumber}_start]`}
                    fieldKey="dayStart"
                    fieldValue={dayStart}
                    onChange={this.onTimeChange}
                />
                <TimeSelector
                    fieldName={`schedules[${scheduleID}][day_${dayNumber}_break_start]`}
                    fieldKey="breakStart"
                    fieldValue={breakStart}
                    startLimit={dayStart}
                    endLimit={dayEnd}
                    disabled={(dayStart === 'none')}
                    onChange={this.onTimeChange}
                />
                <TimeSelector
                    fieldName={`schedules[${scheduleID}][day_${dayNumber}_break_end]`}
                    fieldKey="breakEnd"
                    fieldValue={breakStart === 'none' ? 'none' : breakEnd}
                    startLimit={breakEndStartLimit}
                    endLimit={dayEnd}
                    disabled={(dayStart === 'none')}
                    onChange={this.onTimeChange}
                />
                <TimeSelector
                    fieldName={`schedules[${scheduleID}][day_${dayNumber}_end]`}
                    fieldKey="dayEnd"
                    fieldValue={dayEnd}
                    startLimit={dayEndStartLimit}
                    onChange={this.onTimeChange}
                />
                <span className="demo-total">{this.getDemoCount()}</span>
            </div>
        );
    }
}

module.exports = ScheduleDay;