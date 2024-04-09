const React = require('react');
const ScheduleDay = require('./ScheduleDay.react');
const dayNames = require('../constants/DayNames');
const DatePicker = require('react-datepicker');
const moment = require('moment');

class Schedule extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            stations: this.props.scheduleData.stations || 0,
            id: this.props.scheduleData.id || '',
            startDate: (this.props.scheduleData.start !== null)
                ? moment(this.props.scheduleData.start)
                : moment(),
            endDate: (this.props.scheduleData.end !== null)
                ? moment(this.props.scheduleData.end)
                : moment(),
        };

        this.onStationsChange = this.onStationsChange.bind(this);
        this.onStartDateChange = this.onStartDateChange.bind(this);
        this.onEndDateChange = this.onEndDateChange.bind(this);
    }

    onStationsChange(e) {
        this.setState({stations: e.target.value});
    }

    onStartDateChange(momentDate) {
        this.setState({
            startDate: momentDate,
        });
    }

    onEndDateChange(momentDate) {
        this.setState({
            endDate: momentDate,
        });
    }

    renderDays() {
        const { scheduleData } = this.props;
        const { stations } = this.state;

        return Object.keys(dayNames).map((day, i) => {
            return (
                <ScheduleDay
                    key={i}
                    dayNumber={parseInt(day, 10)}
                    scheduleID={scheduleData.id}
                    dayStart={scheduleData[`day_${day}_start`]}
                    dayEnd={scheduleData[`day_${day}_end`]}
                    breakStart={scheduleData[`day_${day}_break_start`]}
                    breakEnd={scheduleData[`day_${day}_break_end`]}
                    niceName={dayNames[day]}
                    stations={parseInt(stations, 10)}
                />
            );
        });
    }

    render() {
        const {
            scheduleListIndex,
            onDelete,
            onUndoDelete,
            scheduleData,
            intendDelete,
        } = this.props;

        const { stations, startDate, endDate } = this.state;

        const scheduleID = scheduleData.id || '';

        const deleteClass = intendDelete ? ' intend-delete' : '';

        const deleteButton = () => {
            if (intendDelete) {
                return (
                    <span className="delete-message">
                        This Schedule will be deleted after you save.{' '}
                        <a onClick={() => onUndoDelete(scheduleListIndex)}>
                            Undo
                        </a>
                    </span>
                );
            } else {
                return (
                    <a
                        className="delete-schedule"
                        onClick={() => onDelete(scheduleListIndex)}
                    >
                        Delete schedule
                    </a>
                );
            }
        };

        return (
            <div className={`scheduler__schedule${deleteClass} clearfix`}>
                <input
                    type="hidden"
                    name={`schedules[${scheduleID}][id]`}
                    value={scheduleID}
                />
                <input
                    type="hidden"
                    name={`schedules[${scheduleID}][operation]`}
                    value={scheduleData.operation}
                />
                <div className="scheduler__schedule-settings">
                    <div>
                        <span className="table-narrow__label-cell">
                            Start Date
                        </span>
                        <DatePicker
                            className="form-input"
                            dateFormat="YYYY-MM-DD"
                            name={`schedules[${scheduleID}][start]`}
                            onChange={this.onStartDateChange}
                            selected={startDate}
                            isClearable={true}
                        />
                    </div>
                    <div>
                        <span className="table-narrow__label-cell">
                            End Date
                        </span>
                        <DatePicker
                            className="form-input"
                            dateFormat="YYYY-MM-DD"
                            name={`schedules[${scheduleID}][end]`}
                            onChange={this.onEndDateChange}
                            selected={endDate}
                            isClearable={true}
                        />
                    </div>
                    <div>
                        <span className="table-narrow__label-cell">
                            Demo stations
                        </span>
                        <input
                            type="number"
                            min="0"
                            step="1"
                            name={`schedules[${scheduleID}][stations]`}
                            className="form-input centered demo-total-trigger"
                            value={stations}
                            onChange={this.onStationsChange}
                        />
                    </div>
                </div>
                <div className="scheduler__schedule-hours">
                    <div className="scheduler__schedule-label-column">
                        <span className="table-narrow__label-cell">
                            Start
                        </span>
                        <span className="table-narrow__label-cell">
                            Break Start
                        </span>
                        <span className="table-narrow__label-cell">
                            Break End
                        </span>
                        <span className="table-narrow__label-cell">
                            End
                        </span>
                    </div>
                    {this.renderDays()}
                </div>
                {deleteButton()}
            </div>
        );
    }
}

module.exports = Schedule;