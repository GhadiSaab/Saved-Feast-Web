import React, { useState, useEffect } from 'react';

interface CountdownProps {
  targetDate: string;
  onExpire?: () => void;
}

const Countdown: React.FC<CountdownProps> = ({ targetDate, onExpire }) => {
  const [timeLeft, setTimeLeft] = useState<{
    days: number;
    hours: number;
    minutes: number;
    seconds: number;
  }>({ days: 0, hours: 0, minutes: 0, seconds: 0 });

  const [isExpired, setIsExpired] = useState(false);

  useEffect(() => {
    const updateCountdown = () => {
      const now = new Date().getTime();
      const target = new Date(targetDate).getTime();
      const difference = target - now;

      if (difference > 0) {
        const days = Math.floor(difference / (1000 * 60 * 60 * 24));
        const hours = Math.floor(
          (difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        const minutes = Math.floor(
          (difference % (1000 * 60 * 60)) / (1000 * 60)
        );
        const seconds = Math.floor((difference % (1000 * 60)) / 1000);

        setTimeLeft({ days, hours, minutes, seconds });
        setIsExpired(false);
      } else {
        setTimeLeft({ days: 0, hours: 0, minutes: 0, seconds: 0 });
        setIsExpired(true);
        if (onExpire) {
          onExpire();
        }
      }
    };

    updateCountdown();
    const interval = setInterval(updateCountdown, 1000);

    return () => clearInterval(interval);
  }, [targetDate, onExpire]);

  if (isExpired) {
    return (
      <div className="alert alert-danger mb-0 p-2">
        <i className="fas fa-exclamation-triangle me-1"></i>
        <small>Pickup window expired</small>
      </div>
    );
  }

  const { days, hours, minutes, seconds } = timeLeft;

  return (
    <div className="alert alert-info mb-0 p-2">
      <i className="fas fa-clock me-1"></i>
      <small>
        {days > 0 && `${days}d `}
        {hours > 0 && `${hours}h `}
        {minutes > 0 && `${minutes}m `}
        {seconds}s remaining
      </small>
    </div>
  );
};

export default Countdown;
