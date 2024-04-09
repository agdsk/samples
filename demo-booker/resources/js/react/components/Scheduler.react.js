const React = require('react');
const ScheduleList = require('./ScheduleList.react');

const { PropTypes } = React;

function Scheduler({ schedules }) {
    return (
        <div className="scheduler">
            <h3 className="scheduler__title">Location Schedules</h3>
            <ScheduleList schedules={schedules} />
        </div>
    );
}

module.exports = Scheduler;
